<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper;

use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\SepaDirectDebitGuaranteed;

class SepaDirectDebitGuaranteedTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof SepaDirectDebitGuaranteed;
    }

    protected function getResourceName(): string
    {
        return SepaDirectDebitGuaranteed::getResourceName();
    }
}
