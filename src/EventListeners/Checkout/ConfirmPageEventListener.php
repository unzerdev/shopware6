<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\Checkout;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\PaymentFrame\PaymentFrameFactoryInterface;
use UnzerPayment6\Components\Struct\Configuration;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\CreditCardPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\DirectDebitGuaranteedPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\DirectDebitPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\HirePurchasePageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\PaymentFramePageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\PayPalPageExtension;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;
use UnzerPayment6\Installer\PaymentInstaller;

class ConfirmPageEventListener implements EventSubscriberInterface
{
    private const HIRE_PURCHASE_EFFECTIVE_INTEREST_DEFAULT = 4.5;

    /** @var Configuration */
    protected $configData;

    /** @var UnzerPaymentDeviceRepositoryInterface */
    private $deviceRepository;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var PaymentFrameFactoryInterface */
    private $paymentFrameFactory;

    public function __construct(UnzerPaymentDeviceRepositoryInterface $deviceRepository, ConfigReaderInterface $configReader, PaymentFrameFactoryInterface $paymentFrameFactory)
    {
        $this->deviceRepository    = $deviceRepository;
        $this->configReader        = $configReader;
        $this->paymentFrameFactory = $paymentFrameFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class  => 'onCheckoutConfirm',
            AccountEditOrderPageLoadedEvent::class => 'onCheckoutConfirm',
        ];
    }

    public function onCheckoutConfirm(PageLoadedEvent $event): void
    {
        if (!($event instanceof CheckoutConfirmPageLoadedEvent) && !($event instanceof AccountEditOrderPageLoadedEvent)) {
            return;
        }

        $salesChannelContext    = $event->getSalesChannelContext();
        $this->configData       = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $registerCreditCards    = (bool) $this->configData->get(ConfigReader::CONFIG_KEY_REGISTER_CARD, false);
        $registerPayPalAccounts = (bool) $this->configData->get(ConfigReader::CONFIG_KEY_REGISTER_PAYPAL, false);
        $registerDirectDebit    = (bool) $this->configData->get(ConfigReader::CONFIG_KEY_REGISTER_DIRECT_DEBIT, false);

        if (empty($this->configData->get(ConfigReader::CONFIG_KEY_PUBLIC_KEY, ''))
         || empty($this->configData->get(ConfigReader::CONFIG_KEY_PRIVATE_KEY, ''))) {
            $this->removePaymentMethodsFromPage($event);

            return;
        }

        if ($registerCreditCards &&
            $salesChannelContext->getPaymentMethod()->getId() === PaymentInstaller::PAYMENT_ID_CREDIT_CARD
        ) {
            $this->addCreditCardExtension($event);
        }

        if ($registerPayPalAccounts &&
            $salesChannelContext->getPaymentMethod()->getId() === PaymentInstaller::PAYMENT_ID_PAYPAL
        ) {
            $this->addPayPalExtension($event);
        }

        if ($registerDirectDebit &&
            $salesChannelContext->getPaymentMethod()->getId() === PaymentInstaller::PAYMENT_ID_DIRECT_DEBIT
        ) {
            $this->addDirectDebitExtension($event);
        }

        if ($registerDirectDebit &&
            $salesChannelContext->getPaymentMethod()->getId() === PaymentInstaller::PAYMENT_ID_DIRECT_DEBIT_GUARANTEED
        ) {
            $this->addDirectDebitGuaranteedExtension($event);
        }

        if ($salesChannelContext->getPaymentMethod()->getId() === PaymentInstaller::PAYMENT_ID_HIRE_PURCHASE) {
            $this->addHirePurchaseExtension($event);
        }

        $this->addPaymentFrameExtension($event);
    }

    private function addPaymentFrameExtension(PageLoadedEvent $event): void
    {
        $paymentId           = $event->getSalesChannelContext()->getPaymentMethod()->getId();
        $mappedFrameTemplate = $this->paymentFrameFactory->getPaymentFrame($paymentId);

        if (!$mappedFrameTemplate) {
            return;
        }

        $event->getPage()->addExtension('unzerPaymentFrame', (new PaymentFramePageExtension())->setPaymentFrame($mappedFrameTemplate));
    }

    private function addCreditCardExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $creditCards = $this->deviceRepository->getCollectionByCustomer($customer, $event->getContext(), UnzerPaymentDeviceEntity::DEVICE_TYPE_CREDIT_CARD);
        $extension   = (new CreditCardPageExtension())->setDisplayCreditCardSelection(true);

        /** @var UnzerPaymentDeviceEntity $creditCard */
        foreach ($creditCards as $creditCard) {
            $extension->addCreditCard($creditCard);
        }

        $event->getPage()->addExtension('unzerCreditCard', $extension);
    }

    private function addPayPalExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $payPalAccounts = $this->deviceRepository->getCollectionByCustomer($customer, $event->getContext(), UnzerPaymentDeviceEntity::DEVICE_TYPE_PAYPAL);
        $extension      = (new PayPalPageExtension())->setDisplaypayPalAccountselection(true);

        /** @var UnzerPaymentDeviceEntity $payPalAccount */
        foreach ($payPalAccounts as $payPalAccount) {
            $extension->addPayPalAccount($payPalAccount);
        }

        $event->getPage()->addExtension('unzerPayPal', $extension);
    }

    private function addDirectDebitExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $directDebitDevices = $this->deviceRepository->getCollectionByCustomer($customer, $event->getContext(), UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT);
        $extension          = (new DirectDebitPageExtension())->setDisplayDirectDebitDeviceSelection(true);

        /** @var UnzerPaymentDeviceEntity $directDebitDevice */
        foreach ($directDebitDevices as $directDebitDevice) {
            $extension->addDirectDebitDevice($directDebitDevice);
        }

        $event->getPage()->addExtension('unzerDirectDebit', $extension);
    }

    private function addDirectDebitGuaranteedExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $directDebitDevices = $this->deviceRepository->getCollectionByCustomer($customer, $event->getContext(), UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT_GUARANTEED);
        $extension          = (new DirectDebitGuaranteedPageExtension())->setDisplayDirectDebitDeviceSelection(true);

        /** @var UnzerPaymentDeviceEntity $directDebitDevice */
        foreach ($directDebitDevices as $directDebitDevice) {
            $extension->addDirectDebitDevice($directDebitDevice);
        }

        $event->getPage()->addExtension('unzerDirectDebitGuaranteed', $extension);
    }

    private function addHirePurchaseExtension(PageLoadedEvent $event): void
    {
        $extension = new HirePurchasePageExtension();
        $extension->setCurrency($event->getSalesChannelContext()->getCurrency()->getIsoCode());
        $extension->setEffectiveInterest((float) $this->configData->get(ConfigReader::CONFIG_KEY_HIRE_PURCHASE_INTEREST, self::HIRE_PURCHASE_EFFECTIVE_INTEREST_DEFAULT));

        if ($event instanceof CheckoutConfirmPageLoadedEvent) {
            $extension->setAmount($event->getPage()->getCart()->getPrice()->getTotalPrice());
        } elseif ($event instanceof AccountEditOrderPageLoadedEvent) {
            $extension->setAmount($event->getPage()->getOrder()->getPrice()->getTotalPrice());
        }
        $extension->setOrderDate(date('Y-m-d'));

        $event->getPage()->addExtension('unzerHirePurchase', $extension);
    }

    private function removePaymentMethodsFromPage(PageLoadedEvent $event): void
    {
        /** @var AccountEditOrderPage|CheckoutConfirmPage $page */
        $page                       = $event->getPage();
        $salesChannelPaymentMethods = $page->getSalesChannelPaymentMethods();
        $pagePaymentMethods         = $page->getPaymentMethods();

        if ($salesChannelPaymentMethods !== null && $salesChannelPaymentMethods->count() > 0) {
            $page->setSalesChannelPaymentMethods(
                $salesChannelPaymentMethods->filter(function (PaymentMethodEntity $paymentMethod) {
                    return !in_array($paymentMethod->getId(), PaymentInstaller::getPaymentIds(), false);
                })
            );
        }

        if ($pagePaymentMethods !== null && $pagePaymentMethods->count() > 0) {
            $page->setPaymentMethods(
                $pagePaymentMethods->filter(function (PaymentMethodEntity $paymentMethod) {
                    return !in_array($paymentMethod->getId(), PaymentInstaller::getPaymentIds(), false);
                })
            );
        }
    }
}
