<?php

declare(strict_types=1);

namespace HeidelPayment\EventListeners;

use HeidelPayment\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment\Components\Struct\PageExtension\Checkout\ConfirmPageExtension;
use HeidelPayment\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use HeidelPayment\DataAbstractionLayer\Repository\PaymentDevice\HeidelpayPaymentDeviceRepositoryInterface;
use HeidelPayment\Installers\PaymentInstaller;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmEventListener implements EventSubscriberInterface
{
    /** @var HeidelpayPaymentDeviceRepositoryInterface */
    private $deviceRepository;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(HeidelpayPaymentDeviceRepositoryInterface $deviceRepository, ConfigReaderInterface $configReader)
    {
        $this->deviceRepository = $deviceRepository;
        $this->configReader     = $configReader;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirm',
        ];
    }

    public function onCheckoutConfirm(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $customer            = $salesChannelContext->getCustomer();
        $registerCreditCards = (bool) $this->configReader->read($salesChannelContext->getSalesChannel()->getId())->get('registerCreditCard');

        if (!$registerCreditCards || !$customer || $salesChannelContext->getPaymentMethod()->getId() !== PaymentInstaller::PAYMENT_ID_CREDIT_CARD) {
            return;
        }

        $creditCards = $this->deviceRepository->getCollectionByCustomerId($customer->getId(), $salesChannelContext->getContext());

        $extension = new ConfirmPageExtension();
        $extension->setDisplayCreditCardSelection(true);

        /** @var HeidelpayPaymentDeviceEntity $creditCard */
        foreach ($creditCards->getElements() as $creditCard) {
            $extension->addCreditCard($creditCard);
        }

        $event->getPage()->addExtension('heidelpay', $extension);
    }
}
