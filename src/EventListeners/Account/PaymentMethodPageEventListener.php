<?php

declare(strict_types=1);

namespace HeidelPayment6\EventListeners\Account;

use HeidelPayment6\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment6\Components\Struct\PageExtension\Account\PaymentMethodPageExtension;
use HeidelPayment6\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use HeidelPayment6\DataAbstractionLayer\Repository\PaymentDevice\HeidelpayPaymentDeviceRepositoryInterface;
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
        $extension           = new PaymentMethodPageExtension();
        $extension->setDeviceRemoved((bool) $event->getRequest()->get('deviceRemoved'));

        if ($registerCreditCards && $salesChannelContext->getCustomer() !== null) {
            $devices     = $this->deviceRepository->getCollectionByCustomer($salesChannelContext->getCustomer(), $salesChannelContext->getContext());
            $creditCards = $devices->filterByProperty('deviceType', HeidelpayPaymentDeviceEntity::DEVICE_TYPE_CREDIT_CARD)->getElements();

            $extension->addPaymentDevices($creditCards);
        }

        $event->getPage()->addExtension('heidelpay', $extension);
    }
}
