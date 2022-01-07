<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use UnzerPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use UnzerPayment6\UnzerPayment6;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\Shipment;

abstract class AbstractTransitionMapper
{
    public const CONST_KEY_CHARGEBACK = 'ACTION_CHARGEBACK';
    public const CONST_KEY_AUTHORIZE  = 'ACTION_AUTHORIZE';

    /** @var string */
    public const INVALID_TRANSITION = 'invalid';

    /** @var bool */
    protected $isShipmentAllowed = false;

    abstract public function supports(BasePaymentType $paymentType): bool;

    /**
     * @throws TransitionMapperException
     */
    public function getTargetPaymentStatus(Payment $paymentObject): string
    {
        if ($paymentObject->isPending()) {
            return StateMachineTransitionActions::ACTION_REOPEN;
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_TRANSITION) {
                return $status;
            }

            $status = $this->checkForCancellation($paymentObject);

            if ($status !== self::INVALID_TRANSITION) {
                return $status;
            }

            throw new TransitionMapperException($this->getResourceName());
        }

        return $this->checkForRefund($paymentObject, $this->mapPaymentStatus($paymentObject));
    }

    abstract protected function getResourceName(): string;

    protected function mapPaymentStatus(Payment $paymentObject): string
    {
        $status = StateMachineTransitionActions::ACTION_REOPEN;

        if ($paymentObject->isCanceled()) {
            $status = StateMachineTransitionActions::ACTION_CANCEL;
        } elseif ($paymentObject->isChargeBack()) {
            $status = StateMachineTransitionActions::ACTION_CANCEL;

            if ($this->stateMachineTransitionExists(self::CONST_KEY_CHARGEBACK)) {
                return constant(sprintf('%s::%s', StateMachineTransitionActions::class, self::CONST_KEY_CHARGEBACK));
            }
        } elseif ($paymentObject->isPending()) {
            $status = StateMachineTransitionActions::ACTION_REOPEN;
        } elseif ($paymentObject->isPartlyPaid()) {
            $status = StateMachineTransitionActions::ACTION_PAID_PARTIALLY;
        } elseif ($paymentObject->isPaymentReview() || $paymentObject->isCompleted()) {
            $status = StateMachineTransitionActions::ACTION_DO_PAY;
        }

        if ($this->isShipmentAllowed) {
            return $this->checkForShipment($paymentObject, $status);
        }

        return $status;
    }

    protected function checkForRefund(Payment $paymentObject, string $currentStatus = self::INVALID_TRANSITION): string
    {
        $totalAmount     = (int) round($paymentObject->getAmount()->getTotal() * (10 ** UnzerPayment6::MAX_DECIMAL_PRECISION));
        $cancelledAmount = (int) round($paymentObject->getAmount()->getCanceled() * (10 ** UnzerPayment6::MAX_DECIMAL_PRECISION));
        $remainingAmount = (int) round($paymentObject->getAmount()->getRemaining() * (10 ** UnzerPayment6::MAX_DECIMAL_PRECISION));

        if ($cancelledAmount === $totalAmount && $cancelledAmount !== 0 && $totalAmount !== 0 && $remainingAmount === 0
            && $currentStatus !== StateMachineTransitionActions::ACTION_CANCEL
            && !(
                $this->stateMachineTransitionExists(self::CONST_KEY_CHARGEBACK)
                && $currentStatus === constant(sprintf('%s::%s', StateMachineTransitionActions::class, self::CONST_KEY_CHARGEBACK))
            )
        ) {
            return StateMachineTransitionActions::ACTION_REFUND;
        }

        return $currentStatus;
    }

    protected function checkForCancellation(Payment $paymentObject, string $currentStatus = self::INVALID_TRANSITION): string
    {
        $amount    = $paymentObject->getAmount();
        $total     = (int) round($amount->getTotal() * (10 ** UnzerPayment6::MAX_DECIMAL_PRECISION));
        $charged   = (int) round($amount->getCharged() * (10 ** UnzerPayment6::MAX_DECIMAL_PRECISION));
        $cancelled = (int) round($amount->getCanceled() * (10 ** UnzerPayment6::MAX_DECIMAL_PRECISION));

        if ($total === 0 && $charged === 0 && $cancelled === 0 && count($paymentObject->getCancellations()) > 0) {
            return StateMachineTransitionActions::ACTION_CANCEL;
        }

        return $currentStatus;
    }

    protected function checkForShipment(Payment $paymentObject, string $currentStatus = self::INVALID_TRANSITION): string
    {
        $shippedAmount   = 0;
        $totalAmount     = (int) round($paymentObject->getAmount()->getTotal() * (10 ** UnzerPayment6::MAX_DECIMAL_PRECISION));
        $cancelledAmount = (int) round($paymentObject->getAmount()->getCanceled() * (10 ** UnzerPayment6::MAX_DECIMAL_PRECISION));

        if (empty($paymentObject->getShipments())) {
            return $currentStatus;
        }

        /** @var Shipment $shipment */
        foreach ($paymentObject->getShipments() as $shipment) {
            if (!empty($shipment->getAmount())) {
                $shippedAmount += (int) round($shipment->getAmount() * (10 ** UnzerPayment6::MAX_DECIMAL_PRECISION));

                if ($shippedAmount > ($totalAmount - $cancelledAmount)) {
                    $shippedAmount -= (int) round($shipment->getAmount() * (10 ** UnzerPayment6::MAX_DECIMAL_PRECISION));
                }
            }
        }

        if ($shippedAmount === ($totalAmount - $cancelledAmount)) {
            return StateMachineTransitionActions::ACTION_DO_PAY;
        }

        if ($shippedAmount < ($totalAmount - $cancelledAmount)) {
            return StateMachineTransitionActions::ACTION_PAID_PARTIALLY;
        }

        return $currentStatus;
    }

    /**
     * Check if the provided constant name is defined in the StateMachineTransitionActions
     */
    protected function stateMachineTransitionExists(string $stateMachineActionConstantName): bool
    {
        return defined(sprintf('%s::%s', StateMachineTransitionActions::class, $stateMachineActionConstantName));
    }
}
