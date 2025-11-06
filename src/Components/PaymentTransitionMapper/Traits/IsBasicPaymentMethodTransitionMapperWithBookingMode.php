<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper\Traits;

use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use UnzerPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use UnzerSDK\Resources\Payment;

trait IsBasicPaymentMethodTransitionMapperWithBookingMode
{
    use HasBookingMode;

    public function getTargetPaymentStatus(Payment $paymentObject, string $orderTransactionId): string
    {
        try {
            $bookingMode = $this->getBookingMode($orderTransactionId);

            if ($bookingMode !== self::DEFAULT_MODE) {
                return $this->mapForAuthorizeMode($paymentObject);
            }

            return parent::getTargetPaymentStatus($paymentObject, $orderTransactionId);
        } catch (TransitionMapperException $exception) {
            if ($paymentObject->isPending()) {
                return StateMachineTransitionActions::ACTION_REOPEN;
            }

            throw $exception;
        }
    }
}
