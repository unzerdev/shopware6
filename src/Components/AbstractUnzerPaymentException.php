<?php

declare(strict_types=1);

namespace UnzerPayment6\Components;

use Exception;

abstract class AbstractUnzerPaymentException extends Exception
{
    protected string $customerMessage = 'exception/statusMapper';

    public function getCustomerMessage(): string
    {
        return $this->customerMessage;
    }
}
