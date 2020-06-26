<?php

declare(strict_types=1);

namespace HeidelPayment6\EventListeners\Checkout;

use HeidelPayment6\Components\ConfigReader\ConfigReader;
use HeidelPayment6\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment6\Components\PaymentFrame\PaymentFrameFactoryInterface;
use HeidelPayment6\Components\Struct\Configuration;
use HeidelPayment6\Components\Struct\PageExtension\Checkout\Confirm\CreditCardPageExtension;
use HeidelPayment6\Components\Struct\PageExtension\Checkout\Confirm\DirectDebitGuaranteedPageExtension;
use HeidelPayment6\Components\Struct\PageExtension\Checkout\Confirm\DirectDebitPageExtension;
use HeidelPayment6\Components\Struct\PageExtension\Checkout\Confirm\HirePurchasePageExtension;
use HeidelPayment6\Components\Struct\PageExtension\Checkout\Confirm\PaymentFramePageExtension;
use HeidelPayment6\Components\Struct\PageExtension\Checkout\Confirm\PayPalPageExtension;
use HeidelPayment6\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use HeidelPayment6\DataAbstractionLayer\Repository\PaymentDevice\HeidelpayPaymentDeviceRepositoryInterface;
use HeidelPayment6\Installers\PaymentInstaller;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfirmPageEventListener implements EventSubscriberInterface
{
    private const HIRE_PURCHASE_EFFECTIVE_INTEREST_DEFAULT = 4.5;

    /** @var Configuration */
    protected $configData;

    /** @var HeidelpayPaymentDeviceRepositoryInterface */
    private $deviceRepository;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var PaymentFrameFactoryInterface */
    private $paymentFrameFactory;

    public function __construct(HeidelpayPaymentDeviceRepositoryInterface $deviceRepository, ConfigReaderInterface $configReader, PaymentFrameFactoryInterface $paymentFrameFactory)
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
        $registerCreditCards    = (bool) $this->configReader->read($salesChannelContext->getSalesChannel()->getId())->get(ConfigReader::CONFIG_KEY_REGISTER_CARD, false);
        $registerPayPalAccounts = (bool) $this->configReader->read($salesChannelContext->getSalesChannel()->getId())->get(ConfigReader::CONFIG_KEY_REGISTER_PAYPAL, false);
        $registerDirectDebit    = (bool) $this->configReader->read($salesChannelContext->getSalesChannel()->getId())->get(ConfigReader::CONFIG_KEY_REGISTER_DIRECT_DEBIT, false);

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

        $event->getPage()->addExtension('heidelpayPaymentFrame', (new PaymentFramePageExtension())->setPaymentFrame($mappedFrameTemplate));
    }

    private function addCreditCardExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $creditCards = $this->deviceRepository->getCollectionByCustomer($customer, HeidelpayPaymentDeviceEntity::DEVICE_TYPE_CREDIT_CARD, $event->getContext());
        $extension   = (new CreditCardPageExtension())->setDisplayCreditCardSelection(true);

        /** @var HeidelpayPaymentDeviceEntity $creditCard */
        foreach ($creditCards as $creditCard) {
            $extension->addCreditCard($creditCard);
        }

        $event->getPage()->addExtension('heidelpayCreditCard', $extension);
    }

    private function addPayPalExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $payPalAccounts = $this->deviceRepository->getCollectionByCustomer($customer, HeidelpayPaymentDeviceEntity::DEVICE_TYPE_PAYPAL, $event->getContext());
        $extension      = (new PayPalPageExtension())->setDisplaypayPalAccountselection(true);

        /** @var HeidelpayPaymentDeviceEntity $payPalAccount */
        foreach ($payPalAccounts as $payPalAccount) {
            $extension->addPayPalAccount($payPalAccount);
        }

        $event->getPage()->addExtension('heidelpayPayPal', $extension);
    }

    private function addDirectDebitExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $directDebitDevices = $this->deviceRepository->getCollectionByCustomer($customer, HeidelpayPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT, $event->getContext());
        $extension          = (new DirectDebitPageExtension())->setDisplaydirectDebitDeviceselection(true);

        /** @var HeidelpayPaymentDeviceEntity $directDebitDevice */
        foreach ($directDebitDevices as $directDebitDevice) {
            $extension->addDirectDebitDevice($directDebitDevice);
        }

        $event->getPage()->addExtension('heidelpayDirectDebit', $extension);
    }

    private function addDirectDebitGuaranteedExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $directDebitDevices = $this->deviceRepository->getCollectionByCustomer($customer, HeidelpayPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT_GUARANTEED, $event->getContext());
        $extension          = (new DirectDebitGuaranteedPageExtension())->setDisplaydirectDebitDeviceselection(true);

        /** @var HeidelpayPaymentDeviceEntity $directDebitDevice */
        foreach ($directDebitDevices as $directDebitDevice) {
            $extension->addDirectDebitDevice($directDebitDevice);
        }

        $event->getPage()->addExtension('heidelpayDirectDebitGuaranteed', $extension);
    }

    private function addHirePurchaseExtension(PageLoadedEvent $event): void
    {
        $extension = new HirePurchasePageExtension();
        $extension->setCurrency($event->getSalesChannelContext()->getCurrency()->getIsoCode());
        $extension->setEffectiveInterest((float) $this->configData->get('hirePurchaseEffectiveInterest', self::HIRE_PURCHASE_EFFECTIVE_INTEREST_DEFAULT));

        if ($event instanceof CheckoutConfirmPageLoadedEvent) {
            $extension->setAmount($event->getPage()->getCart()->getPrice()->getTotalPrice());
        } elseif ($event instanceof AccountEditOrderPageLoadedEvent) {
            $extension->setAmount($event->getPage()->getOrder()->getPrice()->getTotalPrice());
        }
        $extension->setOrderDate(date('Y-m-d'));

        $event->getPage()->addExtension('heidelpayHirePurchase', $extension);
    }
}
