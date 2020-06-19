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
    public const INVALID_TRANSITION = 'invalid';

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
    }

    abstract protected function getResourceName(): string;

    protected function mapPaymentStatus(Payment $paymentObject): string
    {
        $status = StateMachineTransitionActions::ACTION_REOPEN;

        if ($paymentObject->isCanceled()) {
            return StateMachineTransitionActions::ACTION_CANCEL;
        }

        if ($paymentObject->isPending()) {
            return StateMachineTransitionActions::ACTION_REOPEN;
        }

        if ($paymentObject->isChargeBack()) {
            return StateMachineTransitionActions::ACTION_FAIL;
        }

        if ($paymentObject->isPartlyPaid()) {
            return StateMachineTransitionActions::ACTION_PAID_PARTIALLY;
        }

        if ($paymentObject->isPaymentReview() || $paymentObject->isCompleted()) {
            return StateMachineTransitionActions::ACTION_PAID;
        }

        return $this->checkForRefund($paymentObject, $status);
    }

    protected function checkForRefund(Payment $paymentObject, string $currentStatus = self::INVALID_TRANSITION): string
    {
        $totalAmount     = $this->getAmountByFloat($paymentObject->getAmount()->getTotal());
        $cancelledAmount = $this->getAmountByFloat($paymentObject->getAmount()->getCanceled());
        $remainingAmount = $this->getAmountByFloat($paymentObject->getAmount()->getRemaining());

        if ($cancelledAmount === $totalAmount && $remainingAmount === 0) {
            return StateMachineTransitionActions::ACTION_REFUND;
        }

        return $currentStatus;
    }

    protected function checkForShipment(Payment $paymentObject, string $currentStatus = self::INVALID_TRANSITION): string
    {
        $shippedAmount   = 0;
        $totalAmount     = $this->getAmountByFloat($paymentObject->getAmount()->getTotal());
        $cancelledAmount = $this->getAmountByFloat($paymentObject->getAmount()->getCanceled());

        /** @var Shipment $shipment */
        foreach ($paymentObject->getShipments() as $shipment) {
            if (!empty($shipment->getAmount())) {
                $shippedAmount += $this->getAmountByFloat($shipment->getAmount());
            }
        }

        if ($shippedAmount === ($totalAmount - $cancelledAmount)) {
            return StateMachineTransitionActions::ACTION_PAID;
        }

        if ($shippedAmount < ($totalAmount - $cancelledAmount)) {
            return StateMachineTransitionActions::ACTION_PAID_PARTIALLY;
        }

        return $currentStatus;
    }

    protected function getAmountByFloat(float $amount): int
    {
        $defaultAmount = $amount;
        $stringAmount  = (string) $amount;

        if (strrchr($stringAmount, '.') !== false) {
            return (int) round($amount * (10 ** strlen(substr(strrchr($stringAmount, '.'), 1))));
        }

        return (int) $defaultAmount;
    }
}
