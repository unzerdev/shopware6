<?php

namespace UnzerSDK\Resources\TransactionTypes;

use UnzerSDK\Adapter\HttpAdapterInterface;

/**
 * This represents the chargeback transaction.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Chargeback extends AbstractTransactionType
{
    /** @var string $currency */
    protected $currency;

    /** @var string $paymentReference */
    protected $paymentReference;

    /**
     * @param float|null $amount The amount to be cancelled, is transferred as grossAmount in case of Installment Secured.
     */
    public function __construct(?float $amount = null)
    {
        $this->setAmount($amount);
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return string|null
     */
    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    /**
     * @param string|null $paymentReference
     *
     * @return Cancellation
     */
    public function setPaymentReference(?string $paymentReference): Chargeback
    {
        $this->paymentReference = $paymentReference;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'chargebacks';
    }
}
