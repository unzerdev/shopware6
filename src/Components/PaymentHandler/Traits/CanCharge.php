<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use RuntimeException;
use UnzerPayment6\Components\PaymentHandler\AbstractUnzerPaymentHandler;
use UnzerSDK\Exceptions\UnzerApiException;

trait CanCharge
{
    /**
     * @throws UnzerApiException
     */
    public function charge(string $returnUrl, ?string $recurrenceType = null): string
    {
        if (!$this instanceof AbstractUnzerPaymentHandler) {
            throw new RuntimeException('Trait can only be used in a payment handler context which extends the AbstractUnzerPaymentHandler class');
        }

        if ($this->paymentType === null) {
            throw new RuntimeException('PaymentType can not be null');
        }

        if (!method_exists($this->paymentType, 'charge')) {
            throw new RuntimeException('This payment type does not support direct charge!');
        }

        $paymentResult = $this->paymentType->charge(
            $this->unzerBasket->getTotalValueGross(),
            $this->unzerBasket->getCurrencyCode(),
            $returnUrl,
            $this->unzerCustomer,
            $this->unzerBasket->getOrderId(),
            $this->unzerMetadata,
            $this->unzerBasket,
            true,
            null,
            null,
            $recurrenceType
        );

        $this->payment = $paymentResult->getPayment();

        if ($this->payment !== null && !empty($paymentResult->getRedirectUrl())) {
            return $paymentResult->getRedirectUrl();
        }

        return $returnUrl;
    }
}
