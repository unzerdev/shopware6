<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper\Exception;

use UnzerPayment6\Components\AbstractUnzerPaymentException;

class NoTransitionMapperFoundException extends AbstractUnzerPaymentException
{
    public function __construct(string $paymentName)
    {
        parent::__construct(sprintf('No transition mapper was found for payment method: %s', $paymentName));
    }
}
