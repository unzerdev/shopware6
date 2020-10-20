<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper;

use HeidelPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\TransactionTypes\Shipment;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

abstract class AbstractTransitionMapper
{
    public const HEIDELPAY_MAX_DIGITS = 4;

    public const INVALID_TRANSITION = 'invalid';

    protected $isShipmentAllowed = false;

    abstract public function supports(BasePaymentType $paymentType): bool;

    /**
     * @throws TransitionMapperException
     */
    public function getTargetPaymentStatus(Payment $paymentObject): string
    {
        if ($paymentObject->isPending()) {
            throw new TransitionMapperException($this->getResourceName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

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

            if ($this->stateMachineTransitionExists('ACTION_CHARGEBACK')) {
                $status = StateMachineTransitionActions::ACTION_CHARGEBACK;
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
        $totalAmount     = (int) round($paymentObject->getAmount()->getTotal() * (10 ** self::HEIDELPAY_MAX_DIGITS));
        $cancelledAmount = (int) round($paymentObject->getAmount()->getCanceled() * (10 ** self::HEIDELPAY_MAX_DIGITS));
        $remainingAmount = (int) round($paymentObject->getAmount()->getRemaining() * (10 ** self::HEIDELPAY_MAX_DIGITS));

        if ($cancelledAmount === $totalAmount && $remainingAmount === 0
            && $currentStatus !== StateMachineTransitionActions::ACTION_CANCEL
            && !(
                $this->stateMachineTransitionExists('ACTION_CHARGEBACK')
                && $currentStatus === StateMachineTransitionActions::ACTION_CHARGEBACK
            )
        ) {
            return StateMachineTransitionActions::ACTION_REFUND;
        }

        return $currentStatus;
    }

    protected function checkForShipment(Payment $paymentObject, string $currentStatus = self::INVALID_TRANSITION): string
    {
        $shippedAmount   = 0;
        $totalAmount     = (int) round($paymentObject->getAmount()->getTotal() * (10 ** self::HEIDELPAY_MAX_DIGITS));
        $cancelledAmount = (int) round($paymentObject->getAmount()->getCanceled() * (10 ** self::HEIDELPAY_MAX_DIGITS));

        if (empty($paymentObject->getShipments())) {
            return $currentStatus;
        }

        /** @var Shipment $shipment */
        foreach ($paymentObject->getShipments() as $shipment) {
            if (!empty($shipment->getAmount())) {
                $shippedAmount += (int) round($shipment->getAmount() * (10 ** self::HEIDELPAY_MAX_DIGITS));

                if ($shippedAmount > ($totalAmount - $cancelledAmount)) {
                    $shippedAmount -= (int) round($shipment->getAmount() * (10 ** self::HEIDELPAY_MAX_DIGITS));
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
