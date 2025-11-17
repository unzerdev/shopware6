<?php

declare(strict_types=1);

namespace UnzerPayment6\Components;

abstract class AbstractUnzerPaymentException extends \Exception
{
    /**
     * @var string
     */
    protected $customerMessage = 'exception/statusMapper';

    public function getCustomerMessage(): string
    {
        return $this->customerMessage;
    }
}
