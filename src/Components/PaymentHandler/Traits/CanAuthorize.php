<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use RuntimeException;
use UnzerPayment6\Components\PaymentHandler\AbstractHeidelpayHandler;

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
            $this->unzerBasket->getAmountTotalGross(),
            $this->unzerBasket->getCurrencyCode(),
            $returnUrl,
            $this->unzerCustomer,
            $this->unzerBasket->getOrderId(),
            $this->unzerMetadata,
            $this->unzerBasket,
            true
        );

        $this->payment = $paymentResult->getPayment();

        if ($this->payment !== null && !empty($paymentResult->getRedirectUrl())) {
            return $paymentResult->getRedirectUrl();
        }

        return $returnUrl;
    }
}
