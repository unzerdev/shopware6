<?php

namespace HeidelPayment\Components\PaymentStatusMapper;

use heidelpayPHP\Resources\Payment;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;

class PaymentStatusMapper implements PaymentStatusMapperInterface
{
    /** @var StateMachineRegistry */
    private $stateMachineRegistry;

    public function __construct(StateMachineRegistry $stateMachineRegistry)
    {
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentStatus(Payment $payment, Context $context): StateMachineStateEntity
    {
        return $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            $this->mapPaymentState($payment),
            $context
        );
    }

    /**
     * TODO: change states as needed
     */
    private function mapPaymentState(Payment $payment): string
    {
        if ($payment->isCanceled()) {
            return OrderTransactionStates::STATE_CANCELLED;
        }

        if ($payment->isChargeBack()) {
            return OrderTransactionStates::STATE_CANCELLED;
        }

        if ($payment->isCompleted()) {
            return OrderTransactionStates::STATE_PAID;
        }

        if ($payment->isPartlyPaid()) {
            return OrderTransactionStates::STATE_PARTIALLY_PAID;
        }

        if ($payment->isPaymentReview()) {
            return OrderTransactionStates::STATE_CANCELLED;
        }

        if ($payment->isPending()) {
            return OrderTransactionStates::STATE_OPEN;
        }

        return OrderTransactionStates::STATE_OPEN;
    }
}
