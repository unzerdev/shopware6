<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper\Traits;

use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use UnzerPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use UnzerSDK\Resources\Payment;

trait IsBasicPaymentMethodTransitionMapper
{
    public function getTargetPaymentStatus(Payment $paymentObject): string
    {
        try {
            return parent::getTargetPaymentStatus($paymentObject);
        } catch (TransitionMapperException $exception) {
            if ($paymentObject->isPending()) {
                return StateMachineTransitionActions::ACTION_REOPEN;
            }

            throw $exception;
        }
    }
}
