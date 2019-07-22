<?php

namespace HeidelPayment\Services\Heidelpay\Webhooks\Handlers;

use HeidelPayment\Components\Client\ClientFactory;
use HeidelPayment\Components\PaymentStatusMapper\PaymentStatusMapperInterface;
use HeidelPayment\Components\Struct\Webhook;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Payment;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\Exception\StateMachineNotFoundException;

/**
 * @property Payment $resource
 */
class PaymentStatusWebhookHandler implements WebhookHandlerInterface
{
    /** @var PaymentStatusMapperInterface */
    private $paymentStatusMapper;

    /** @var ClientFactory */
    private $clientFactory;

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    public function __construct(
        PaymentStatusMapperInterface $paymentStatusMapper,
        ClientFactory $clientFactory,
        EntityRepositoryInterface $orderTransactionRepository
    ) {
        $this->paymentStatusMapper = $paymentStatusMapper;
        $this->clientFactory = $clientFactory;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Webhook $webhook, SalesChannelContext $context): bool
    {
        return true;
    }

    /**
     *
     * TODO: use stateMachineRegistry->transition for the actual write process
     * TODO: transition needs StateMachineTransitionActions, we need to map these instead of the payment status
     *
     * {@inheritdoc}
     */
    public function execute(Webhook $webhook, SalesChannelContext $context): void
    {
        $client = $client = $this->clientFactory->createClient();
        $payment = $client->getResourceService()->fetchResourceByUrl($webhook->getRetrieveUrl());

        if (!$payment instanceof Payment) {
            return;
        }

        $paymentState = $this->paymentStatusMapper->getPaymentStatus($payment, $context->getContext());
        $transaction = $this->getOrderTransaction($payment, $context->getContext());

        if (null === $transaction) {
            return;
        }

        $payload = [
            'id' => $transaction->getId(),
            'stateId' => $paymentState->getId()
        ];

        $this->orderTransactionRepository->update([$payload], $context->getContext());
    }

    private function getOrderTransaction(Payment $payment, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$payment->getOrderId()]);

        $this->orderTransactionRepository->search($criteria, $context)->first();
    }
}
