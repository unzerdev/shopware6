<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use RuntimeException;
use UnzerPayment6\Components\PaymentHandler\AbstractUnzerPaymentHandler;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\EmbeddedResources\RiskData;
use UnzerSDK\Resources\TransactionTypes\Charge;

trait CanCharge
{
    /**
     * @throws UnzerApiException
     */
    public function charge(
        string $returnUrl,
        ?string $recurrenceType = null,
        ?RiskData $riskData = null
    ): string
    {
        if ($this->unzerClient === null) {
            throw new RuntimeException('unzerClient can not be null');
        }

        if (!method_exists($this->unzerClient, 'performAuthorization')) {
            throw new RuntimeException('The SDK Version is older then expected');
        }

        if ($this->paymentType === null) {
            throw new RuntimeException('PaymentType can not be null');
        }

        $charge = new Charge(
            $this->unzerBasket->getTotalValueGross(),
            $this->unzerBasket->getCurrencyCode(),
            $returnUrl
        );

        $charge->setOrderId($this->unzerBasket->getOrderId());
        $charge->setCard3ds(true);

        if ($recurrenceType !== null) {
            $charge->setRecurrenceType($recurrenceType);
        }

        if ($riskData !== null) {
            $charge->setRiskData($riskData);
        }

        $paymentResult = $this->unzerClient->performCharge(
            $charge,
            $this->paymentType,
            $this->unzerCustomer,
            $this->unzerMetadata,
            $this->unzerBasket
        );

        $this->payment = $paymentResult->getPayment();

        if ($this->payment !== null && !empty($paymentResult->getRedirectUrl())) {
            return $paymentResult->getRedirectUrl();
        }

        return $returnUrl;
    }
}
