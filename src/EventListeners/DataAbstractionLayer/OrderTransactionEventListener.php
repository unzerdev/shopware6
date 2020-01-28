<?php

declare(strict_types=1);

namespace HeidelPayment6\EventListeners\DataAbstractionLayer;

use HeidelPayment6\DataAbstractionLayer\Repository\TransferInfo\HeidelpayTransferInfoRepositoryInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderTransactionEventListener implements EventSubscriberInterface
{
    /** @var HeidelpayTransferInfoRepositoryInterface */
    private $transferInfoRepository;

    public function __construct(HeidelpayTransferInfoRepositoryInterface $transferInfoRepository)
    {
        $this->transferInfoRepository = $transferInfoRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_TRANSACTION_LOADED_EVENT => 'onLoadOrderTransactionEntity',
        ];
    }

    public function onLoadOrderTransactionEntity(EntityLoadedEvent $event): void
    {
        /** @var OrderTransactionEntity $transaction */
        foreach ($event->getEntities() as $transaction) {
            $transferInfo = $this->transferInfoRepository->read($transaction->getId(), $event->getContext());

            $transaction->addExtension('heidelpayTransferInfo', $transferInfo);
        }
    }
}
