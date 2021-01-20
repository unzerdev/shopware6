<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Invoice;

class InvoiceTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Invoice;
    }

    protected function getResourceName(): string
    {
        return Invoice::getResourceName();
    }

    protected function isPendingAllowed(): bool
    {
        return true;
    }
}
