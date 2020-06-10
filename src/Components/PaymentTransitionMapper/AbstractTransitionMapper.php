<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper;

use HeidelPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use HeidelPayment6\Installers\PaymentInstaller;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\TransactionTypes\Shipment;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

abstract class AbstractTransitionMapper
{
    public const PAYMENT_STATUS_PENDING_ALLOWED = [
        PaymentInstaller::PAYMENT_ID_PRE_PAYMENT,
        PaymentInstaller::PAYMENT_ID_INVOICE,
    ];

    public const INVALID_STATUS = 'invalid';

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
            $status = StateMachineTransitionActions::ACTION_CANCEL;
        } elseif ($paymentObject->isPartlyPaid()) {
            $status = StateMachineTransitionActions::ACTION_PAID_PARTIALLY;
        } elseif ($paymentObject->isPaymentReview()) {
            $status = StateMachineTransitionActions::ACTION_DO_PAY;
        } elseif ($paymentObject->isCompleted()) {
            $status = StateMachineTransitionActions::ACTION_DO_PAY;
        }

        return $this->checkForRefund($paymentObject, $status);
    }

    protected function checkForRefund(Payment $paymentObject, string $currentStatus = self::INVALID_STATUS): string
    {
        $totalAmount     = $this->getAmountByFloat($paymentObject->getAmount()->getTotal());
        $cancelledAmount = $this->getAmountByFloat($paymentObject->getAmount()->getCanceled());
        $remainingAmount = $this->getAmountByFloat($paymentObject->getAmount()->getRemaining());

        if ($cancelledAmount === $totalAmount && $remainingAmount === 0) {
            return StateMachineTransitionActions::ACTION_REFUND;
        }

        return $currentStatus;
    }

    protected function checkForShipment(Payment $paymentObject, string $currentStatus = self::INVALID_STATUS): string
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
        $amount        = (string) $amount;

        if (strrchr($amount, '.') !== false) {
            return (int) ($amount * (10 ** strlen(substr(strrchr($amount, '.'), 1))));
        }

        return (int) $defaultAmount;
    }
}
