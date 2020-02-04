<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\WebhookHandler;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment6\Components\Struct\Webhook;
use HeidelPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use heidelpayPHP\Resources\Payment;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @property Payment $resource
 */
class PaymentStatusWebhookHandler implements WebhookHandlerInterface
{
    /** @var TransactionStateHandlerInterface */
    private $transactionStateHandler;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    public function __construct(
        TransactionStateHandlerInterface $transactionStateHandler,
        ClientFactoryInterface $clientFactory,
        EntityRepositoryInterface $orderTransactionRepository
    ) {
        $this->transactionStateHandler    = $transactionStateHandler;
        $this->clientFactory              = $clientFactory;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Webhook $webhook, SalesChannelContext $context): bool
    {
        return stripos($webhook->getEvent(), 'payment.') !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Webhook $webhook, SalesChannelContext $context): void
    {
        $client  = $this->clientFactory->createClient();
        $payment = $client->getResourceService()->fetchResourceByUrl($webhook->getRetrieveUrl());

        if (!$payment instanceof Payment) {
            return;
        }

        $transaction = $this->getOrderTransaction($payment, $context->getContext());

        if ($transaction === null) {
            return;
        }

        $this->transactionStateHandler->transformTransactionState(
            $transaction,
            $payment,
            $context->getContext()
        );
    }

    private function getOrderTransaction(Payment $payment, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$payment->getOrderId()]);

        return $this->orderTransactionRepository->search($criteria, $context)->first();
    }
}
