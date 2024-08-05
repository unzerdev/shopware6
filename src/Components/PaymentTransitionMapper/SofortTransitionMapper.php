<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use UnzerPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use UnzerPayment6\Components\PaymentTransitionMapper\Traits\IsBasicPaymentMethodTransitionMapper;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Sofort;

class SofortTransitionMapper extends AbstractTransitionMapper
{
    use IsBasicPaymentMethodTransitionMapper;
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Sofort;
    }

    protected function getResourceName(): string
    {
        return Sofort::getResourceName();
    }
}
