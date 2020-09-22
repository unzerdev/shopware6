<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\SepaDirectDebit;

class SepaDirectDebitTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof SepaDirectDebit;
    }

    protected function getResourceName(): string
    {
        return SepaDirectDebit::getResourceName();
    }
}
