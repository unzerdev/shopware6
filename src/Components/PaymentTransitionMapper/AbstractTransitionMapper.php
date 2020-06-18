<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper;

use HeidelPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Resources\TransactionTypes\Shipment;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

abstract class AbstractTransitionMapper
{
    public const INVALID_TRANSITION = 'invalid';

    abstract public function supports(BasePaymentType $paymentType): bool;

    /**
     * @throws TransitionMapperException
     */
    abstract public function getTargetPaymentStatus(Payment $payment): string;

    protected function mapPaymentStatus(Payment $paymentObject): string
    {
        $status = StateMachineTransitionActions::ACTION_REOPEN;

        if ($paymentObject->isCanceled()) {
            $status = StateMachineTransitionActions::ACTION_CANCEL;
        } elseif ($paymentObject->isPending()) {
            $status = StateMachineTransitionActions::ACTION_REOPEN;
        } elseif ($paymentObject->isChargeBack()) {
            $status = StateMachineTransitionActions::ACTION_FAIL;
        } elseif ($paymentObject->isPartlyPaid()) {
            $status = StateMachineTransitionActions::ACTION_PAID_PARTIALLY;
        } elseif ($paymentObject->isPaymentReview()) {
            $status = StateMachineTransitionActions::ACTION_DO_PAY;
        } elseif ($paymentObject->isCompleted()) {
            $status = StateMachineTransitionActions::ACTION_DO_PAY;
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
            return StateMachineTransitionActions::ACTION_DO_PAY;
        }

        if ($shippedAmount < ($totalAmount - $cancelledAmount)) {
            return StateMachineTransitionActions::ACTION_PAID_PARTIALLY;
        }

        return $currentStatus;
    }

    protected function getMessageFromSnippet(string $snippetName = 'paymentCancelled', string $snippetNamespace = 'frontend/heidelpay/checkout/errors'): string
    {
//        TODO
        return '';
    }

    protected function getMessageFromPaymentTransaction(Payment $payment): string
    {
        $transaction = $payment->getAuthorization();

        if ($transaction instanceof Authorization) {
            return $transaction->getMessage()->getCustomer();
        }

        $transaction = $payment->getChargeByIndex(0);

        if ($transaction instanceof Charge) {
            return $transaction->getMessage()->getCustomer();
        }

        return $this->getMessageFromSnippet();
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
