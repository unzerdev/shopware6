<?php

declare(strict_types=1);

namespace HeidelPayment6\Components;

use Exception;

abstract class AbstractHeidelPaymentException extends Exception
{
    /** @var string */
    protected $customerMessage = 'exception/statusMapper';

    public function getCustomerMessage(): string
    {
        return $this->customerMessage;
    }
}
