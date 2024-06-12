<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Exception;

use Shopware\Core\Checkout\Payment\PaymentException;
use Symfony\Component\HttpFoundation\Response;
use UnzerSDK\Exceptions\UnzerApiException;

class UnzerPaymentProcessException extends PaymentException
{
    /** @var string */
    protected string $orderId;

    /** @var UnzerApiException */
    protected $originalException;

    public function __construct(string $orderId, string $orderTransactionId, UnzerApiException $apiException)
    {
        $this->orderId           = $orderId;
        $this->originalException = $apiException;

        parent::__construct(
            Response::HTTP_BAD_REQUEST,
            self::PAYMENT_ASYNC_PROCESS_INTERRUPTED,
            'The asynchronous payment process was interrupted due to the following error:' . \PHP_EOL . '{{ errorMessage }}',
            [
                'errorMessage' => $apiException->getMerchantMessage(),
                'orderTransactionId' => $orderTransactionId,
            ]
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
