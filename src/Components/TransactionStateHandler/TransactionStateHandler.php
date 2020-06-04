<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\TransactionStateHandler;

use HeidelPayment6\Components\DependencyInjection\Factory\PaymentTransitionMapperFactory;
use heidelpayPHP\Resources\Payment;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class TransactionStateHandler implements TransactionStateHandlerInterface
{
    /** @var StateMachineRegistry */
    private $stateMachineRegistry;

    /** @var PaymentTransitionMapperFactory */
    private $transitionMapperFactory;

    public function __construct(
        StateMachineRegistry $stateMachineRegistry,
        PaymentTransitionMapperFactory $transitionMapperFactory
    ) {
        $this->stateMachineRegistry = $stateMachineRegistry;
        $this->transitionMapperFactory = $transitionMapperFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function transformTransactionState(
        OrderTransactionEntity $transaction,
        Payment $payment,
        Context $context
    ): void {
        $transitionMapper = $this->transitionMapperFactory->getTransitionMapper($payment->getPaymentType());

        if(empty($transitionMapper)) {
            return;
        }

        $transition = $transitionMapper->getTargetPaymentStatus($payment);
        $transactionId = $transaction->getId();

        try {
            $this->stateMachineRegistry->transition(
                new Transition(
                    OrderTransactionDefinition::ENTITY_NAME,
                    $transactionId,
                    $transition,
                    'stateId'
                ),
                $context
            );
        } catch (IllegalTransitionException $exception) {
            // false positive handling (state to state) like open -> open, paid -> paid, etc.
        }
    }
}
