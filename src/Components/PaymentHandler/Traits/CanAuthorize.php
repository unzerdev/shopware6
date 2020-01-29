<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentHandler\Traits;

use HeidelPayment6\Components\PaymentHandler\AbstractHeidelpayHandler;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use RuntimeException;

trait CanAuthorize
{
    /**
     * @throws HeidelpayApiException
     */
    public function authorize(string $returnUrl): string
    {
        if (!$this instanceof AbstractHeidelpayHandler) {
            throw new RuntimeException('Trait can only be used in a payment handler context which extends the AbstractHeidelpayHandler class');
        }

        if ($this->paymentType === null) {
            throw new RuntimeException('PaymentType can not be null');
        }

        if (!method_exists($this->paymentType, 'authorize')) {
            throw new RuntimeException('This payment type does not support authorization');
        }

        $paymentResult = $this->paymentType->authorize(
            $this->heidelpayBasket->getAmountTotalGross(),
            $this->heidelpayBasket->getCurrencyCode(),
            $returnUrl,
            $this->heidelpayCustomer,
            $this->heidelpayBasket->getOrderId(),
            $this->heidelpayMetadata,
            $this->heidelpayBasket,
            true
        );

        if ($paymentResult->getPayment() && !empty($paymentResult->getRedirectUrl())) {
            return $paymentResult->getRedirectUrl();
        }

        return $returnUrl;
    }
}
