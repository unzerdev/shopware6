<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\InvoiceFactoring;

class InvoiceFactoringTransitionMapper extends AbstractTransitionMapper
{
    /** @var bool */
    protected $isShipmentAllowed = true;

    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof InvoiceFactoring;
    }

    protected function getResourceName(): string
    {
        return InvoiceFactoring::getResourceName();
    }

    protected function isPendingAllowed(): bool
    {
        return true;
    }
}
