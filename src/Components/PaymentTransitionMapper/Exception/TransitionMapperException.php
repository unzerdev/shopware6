<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper\Exception;

use UnzerPayment6\Components\AbstractUnzerPaymentException;

class TransitionMapperException extends AbstractUnzerPaymentException
{
    public function __construct(string $paymentName)
    {
        parent::__construct(sprintf('Payment status transition is not allowed for payment method: %s', $paymentName));
    }
}
