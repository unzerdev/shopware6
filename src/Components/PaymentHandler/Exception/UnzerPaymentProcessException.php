<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Exception;

use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use UnzerSDK\Exceptions\UnzerApiException;

class UnzerPaymentProcessException extends AsyncPaymentProcessException
{
    /** @var string */
    protected $orderId;

    /** @var UnzerApiException */
    protected $originalException;

    public function __construct(
        string $orderId,
        UnzerApiException $apiException
    ) {
        $this->orderId           = $orderId;
        $this->originalException = $apiException;

        parent::__construct(
            $orderId,
            $apiException->getMerchantMessage(),
        );
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getClientMessage(): string
    {
        return $this->originalException->getClientMessage();
    }

    public function getMerchantMessage(): string
    {
        return $this->originalException->getMerchantMessage();
    }
}
