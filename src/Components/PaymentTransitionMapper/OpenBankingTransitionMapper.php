<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use UnzerPayment6\Components\PaymentTransitionMapper\Traits\IsBasicPaymentMethodTransitionMapper;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\OpenbankingPis;

class OpenBankingTransitionMapper extends AbstractTransitionMapper
{
    use IsBasicPaymentMethodTransitionMapper;

    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof OpenbankingPis;
    }

    protected function getResourceName(): string
    {
        return OpenbankingPis::getResourceName();
    }
}
