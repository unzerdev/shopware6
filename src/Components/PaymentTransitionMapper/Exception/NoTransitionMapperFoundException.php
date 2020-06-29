<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper\Exception;

use HeidelPayment6\Components\AbstractHeidelPaymentException;

class NoTransitionMapperFoundException extends AbstractHeidelPaymentException
{
    public function __construct(string $paymentName)
    {
        parent::__construct(sprintf('No transition mapper was found for payment method: %s', $paymentName));
    }
}
