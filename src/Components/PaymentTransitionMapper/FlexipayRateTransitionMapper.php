<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper;

use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\HirePurchaseDirectDebit;

class FlexipayRateTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof HirePurchaseDirectDebit;
    }

    protected function getResourceName(): string
    {
        return HirePurchaseDirectDebit::getResourceName();
    }
}
