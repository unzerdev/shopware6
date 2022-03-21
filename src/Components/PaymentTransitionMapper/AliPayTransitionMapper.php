<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use UnzerPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Alipay;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

class AliPayTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Alipay;
    }

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

    protected function getResourceName(): string
    {
        return Alipay::getResourceName();
    }
}
