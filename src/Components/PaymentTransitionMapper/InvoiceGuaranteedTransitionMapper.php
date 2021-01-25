<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\InvoiceGuaranteed;

class InvoiceGuaranteedTransitionMapper extends AbstractTransitionMapper
{
    /** @var bool */
    protected $isShipmentAllowed = true;

    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof InvoiceGuaranteed;
    }

    protected function getResourceName(): string
    {
        return InvoiceGuaranteed::getResourceName();
    }

    protected function isPendingAllowed(): bool
    {
        return true;
    }
}
