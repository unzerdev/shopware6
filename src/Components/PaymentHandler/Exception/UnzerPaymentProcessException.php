<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Exception;

use UnzerSDK\Exceptions\UnzerApiException;

class UnzerPaymentProcessException extends UnzerApiException
{
    /** @var string */
    protected $orderId;

    public function __construct(
        string $orderId,
        UnzerApiException $apiException
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
