<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebitSecured;

class SepaDirectDebitSecuredTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof SepaDirectDebitSecured;
    }

    protected function getResourceName(): string
    {
        return SepaDirectDebitSecured::getResourceName();
    }
}
