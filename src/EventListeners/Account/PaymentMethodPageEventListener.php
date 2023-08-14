<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\Account;

use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnzerPayment6\Components\Struct\PageExtension\Account\PaymentMethodPageExtension;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;

class PaymentMethodPageEventListener implements EventSubscriberInterface
{
    /** @var UnzerPaymentDeviceRepositoryInterface */
    private $deviceRepository;

    public function __construct(UnzerPaymentDeviceRepositoryInterface $deviceRepository)
    {
        $this->deviceRepository = $deviceRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AccountPaymentMethodPageLoadedEvent::class => 'onLoadAccountPaymentMethod',
        ];
    }

    public function onLoadAccountPaymentMethod(AccountPaymentMethodPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();

        if (!$salesChannelContext->getCustomer()) {
            return;
        }

        $extension = new PaymentMethodPageExtension();
        $devices   = $this->deviceRepository->getCollectionByCustomer($salesChannelContext->getCustomer(), $salesChannelContext->getContext());
        $extension->setDeviceRemoved((bool) $event->getRequest()->get('deviceRemoved'));

        if ($salesChannelContext->getCustomer() !== null) {
            $creditCards               = $devices->filterByProperty('deviceType', UnzerPaymentDeviceEntity::DEVICE_TYPE_CREDIT_CARD)->getElements();
            $directDebitDevices        = $devices->filterByProperty('deviceType', UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT)->getElements();
            $directDebitSecuredDevices = $devices->filterByProperty('deviceType', UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT_SECURED)->getElements();
            $payPalAccounts            = $devices->filterByProperty('deviceType', UnzerPaymentDeviceEntity::DEVICE_TYPE_PAYPAL)->getElements();

            $extension->addPaymentDevices($creditCards);
            $extension->addPaymentDevices($directDebitDevices);
            $extension->addPaymentDevices($directDebitSecuredDevices);
            $extension->addPaymentDevices($payPalAccounts);
        }

        $event->getPage()->addExtension(PaymentMethodPageExtension::EXTENSION_NAME, $extension);
    }
}
