<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\TransactionStateHandler;

use heidelpayPHP\Resources\Payment;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use UnzerPayment6\Components\DependencyInjection\Factory\PaymentTransitionMapperFactory;
use UnzerPayment6\Components\PaymentTransitionMapper\Exception\NoTransitionMapperFoundException;
use UnzerPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;

class TransactionStateHandler implements TransactionStateHandlerInterface
{
    /** @var StateMachineRegistry */
    private $stateMachineRegistry;

    /** @var PaymentTransitionMapperFactory */
    private $transitionMapperFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        StateMachineRegistry $stateMachineRegistry,
        PaymentTransitionMapperFactory $transitionMapperFactory,
        LoggerInterface $logger
    ) {
        $this->stateMachineRegistry    = $stateMachineRegistry;
        $this->transitionMapperFactory = $transitionMapperFactory;
        $this->logger                  = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function transformTransactionState(
        string $transactionId,
        Payment $payment,
        Context $context
    ): void {
        if ($payment->getPaymentType() === null) {
            $this->logger->error(sprintf('The payment has no payment type for transition mapping. TransactionId: %s', $transactionId), [
                'payment' => $payment,
            ]);

            return;
        }

        $transition = $this->getTargetTransition($payment);

        if (empty($transition)) {
            $this->logger->error('Due to empty transition, FAIL is executed');

            $this->executeTransition($transactionId, StateMachineTransitionActions::ACTION_FAIL, $context);

            throw new RuntimeException('Invalid transition status');
        }

        $this->executeTransition($transactionId, $transition, $context);
    }

    public function fail(string $transactionId, Context $context): void
    {
        $this->executeTransition(
            $transactionId,
            StateMachineTransitionActions::ACTION_FAIL,
            $context
        );
    }

    protected function getTargetTransition(Payment $payment): string
    {
        try {
            $transitionMapper = $this->transitionMapperFactory->getTransitionMapper($payment->getPaymentType());
            $transition       = $transitionMapper->getTargetPaymentStatus($payment);
        } catch (NoTransitionMapperFoundException | TransitionMapperException $exception) {
            $this->logger->error($exception->getMessage(), [
                'code'  => $exception->getCode(),
                'file'  => $exception->getFile(),
                'line'  => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return $transition ?? '';
    }

    protected function executeTransition(string $transactionId, string $transition, Context $context): void
    {
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

        // If payment should be in state "paid", `do_pay` is given -> finalize state
        if ($transition === StateMachineTransitionActions::ACTION_DO_PAY) {
            $this->logger->debug(
                sprintf(
                    '%s transition is executed as fallback for %s',
                    StateMachineTransitionActions::ACTION_PAID,
                    StateMachineTransitionActions::ACTION_DO_PAY
                )
            );

            $this->executeTransition($transactionId, StateMachineTransitionActions::ACTION_PAID, $context);
        }
    }
}
