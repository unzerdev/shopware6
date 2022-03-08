<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Exception;

use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use UnzerSDK\Exceptions\UnzerApiException;

class UnzerPaymentProcessException extends AsyncPaymentProcessException
{
    /** @var string */
    protected $transactionId;

    /** @var UnzerApiException */
    protected $originalException;

    public function __construct(
        string $transactionId,
        UnzerApiException $apiException
    ) {
        $this->transactionId = $transactionId;

        parent::__construct(
            $transactionId,
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
}
