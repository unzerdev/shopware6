<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper\Exception;

use HeidelPayment6\Components\AbstractHeidelPaymentException;

class TransitionMapperException extends AbstractHeidelPaymentException
{
    public function __construct(string $paymentName)
    {
        parent::__construct(sprintf('Payment status is not allowed for payment method: %s', $paymentName));
    }
}
