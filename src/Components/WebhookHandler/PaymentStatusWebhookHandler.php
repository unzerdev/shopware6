<?php

declare(strict_types=1);

namespace HeidelPayment\Components\WebhookHandler;

use HeidelPayment\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment\Components\Struct\Webhook;
use HeidelPayment\Components\TransactionStateHandler\TransactionStateHandlerInterface;
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
     * TODO: Decide if stateMachineRegistry->transition for the actual write process should be used
     * TODO: transition needs StateMachineTransitionActions, we need to map actions instead of the payment status
     *
     * {@inheritdoc}
     */
    public function execute(Webhook $webhook, SalesChannelContext $context): void
    {
        $client  = $client  = $this->clientFactory->createClient();
        $payment = $client->getResourceService()->fetchResourceByUrl($webhook->getRetrieveUrl());

        if (!$payment instanceof Payment) {
            return;
        }

        $transaction = $this->getOrderTransaction($payment, $context->getContext());

        if (null === $transaction) {
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

        $this->orderTransactionRepository->search($criteria, $context)->first();
    }
}
