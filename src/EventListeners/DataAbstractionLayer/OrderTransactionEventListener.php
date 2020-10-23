<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\DataAbstractionLayer;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnzerPayment6\DataAbstractionLayer\Repository\TransferInfo\UnzerPaymentTransferInfoRepositoryInterface;

class OrderTransactionEventListener implements EventSubscriberInterface
{
    /** @var UnzerPaymentTransferInfoRepositoryInterface */
    private $transferInfoRepository;

    public function __construct(UnzerPaymentTransferInfoRepositoryInterface $transferInfoRepository)
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

            $transaction->addExtension('unzerTransferInfo', $transferInfo);
        }
    }
}
