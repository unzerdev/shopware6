<?php

declare(strict_types=1);

namespace HeidelPayment6\EventListeners\Checkout;

use HeidelPayment6\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment6\Components\PaymentFrame\PaymentFrameFactoryInterface;
use HeidelPayment6\Components\Struct\Configuration;
use HeidelPayment6\Components\Struct\PageExtension\Checkout\Confirm\CreditCardPageExtension;
use HeidelPayment6\Components\Struct\PageExtension\Checkout\Confirm\HirePurchasePageExtension;
use HeidelPayment6\Components\Struct\PageExtension\Checkout\Confirm\PaymentFramePageExtension;
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

        $salesChannelContext = $event->getSalesChannelContext();
        $this->configData    = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $registerCreditCards = (bool) $this->configData->get('registerCreditCard');

        //Extension for credit card payments
        if ($registerCreditCards &&
            $salesChannelContext->getPaymentMethod()->getId() === PaymentInstaller::PAYMENT_ID_CREDIT_CARD
        ) {
            $this->addCreditCardExtension($event);
        }

        //Extension for hire purchase payments
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

        $creditCards = $this->deviceRepository->getCollectionByCustomer($customer, $event->getContext());

        $extension = new CreditCardPageExtension();
        $extension->setDisplayCreditCardSelection(true);

        /** @var HeidelpayPaymentDeviceEntity $creditCard */
        foreach ($creditCards->getElements() as $creditCard) {
            $extension->addCreditCard($creditCard);
        }

        $event->getPage()->addExtension('heidelpayCreditCard', $extension);
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
