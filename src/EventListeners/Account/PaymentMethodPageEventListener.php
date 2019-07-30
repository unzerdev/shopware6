<?php

declare(strict_types=1);

namespace HeidelPayment\EventListeners\Account;

use HeidelPayment\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment\Components\Struct\PageExtension\Account\PaymentMethodPageExtension;
use HeidelPayment\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use HeidelPayment\DataAbstractionLayer\Repository\PaymentDevice\HeidelpayPaymentDeviceRepositoryInterface;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentMethodPageEventListener implements EventSubscriberInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var HeidelpayPaymentDeviceRepositoryInterface */
    private $deviceRepository;

    public function __construct(ConfigReaderInterface $configReader, HeidelpayPaymentDeviceRepositoryInterface $deviceRepository)
    {
        $this->configReader     = $configReader;
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

        $registerCreditCards = $this->configReader->read($salesChannelContext->getSalesChannel()->getId())->get('registerCreditCard');

        $extension = new PaymentMethodPageExtension();
        $extension->setDeviceRemoved((bool) $event->getRequest()->get('deviceRemoved'));

        $devices = $this->deviceRepository->getCollectionByCustomerId($salesChannelContext->getCustomer()->getId(), $salesChannelContext->getContext());

        if ($registerCreditCards) {
            $creditCards = $devices->filterByProperty('deviceType', HeidelpayPaymentDeviceEntity::DEVICE_TYPE_CREDIT_CARD)->getElements();

            $extension->addPaymentDevices($creditCards);
        }

        $event->getPage()->addExtension('heidelpay', $extension);
    }
}
