<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Exception;

use heidelpayPHP\Exceptions\HeidelpayApiException;

class UnzerPaymentProcessException extends HeidelpayApiException
{
    /** @var string */
    protected $orderId;

    public function __construct(
        string $orderId,
        HeidelpayApiException $apiException
    ) {
        $this->orderId = $orderId;

        parent::__construct(
            $apiException->getMerchantMessage(),
            $apiException->getClientMessage(),
            $apiException->getCode(),
            $apiException->getErrorId()
        );
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}
