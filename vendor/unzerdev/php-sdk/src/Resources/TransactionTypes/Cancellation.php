<?php

namespace UnzerSDK\Resources\TransactionTypes;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use function in_array;

/**
 * This represents the cancel transaction.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Cancellation extends AbstractTransactionType
{
    /**
     * The cancellation amount will be transferred as grossAmount in case of Installment Secured payment type.
     *
     * @var float $amount
     */
    protected $amount;

    /** @var string $reasonCode */
    protected $reasonCode;

    /** @var string $paymentReference */
    protected $paymentReference;

    /**
     * The net value of the cancellation amount (Installment Secured only).
     *
     * @var float $amountNet
     */
    protected $amountNet;

    /**
     * The vat value of the cancellation amount (Installment Secured only).
     *
     * @var float $amountVat
     */
    protected $amountVat;

    /**
     * Authorization constructor.
     *
     * @param float|null $amount The amount to be cancelled, is transferred as grossAmount in case of Installment Secured.
     */
    public function __construct(?float $amount = null)
    {
        $this->setAmount($amount);
    }

    /**
     * Returns the cancellationAmount (equals grossAmount in case of Installment Secured).
     *
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * Sets the cancellationAmount (equals grossAmount in case of Installment Secured).
     *
     * @param float|null $amount
     *
     * @return Cancellation
     */
    public function setAmount(?float $amount): self
    {
        $this->amount = $amount !== null ? round($amount, 4) : null;
        return $this;
    }

    /**
     * Returns the reason code of the cancellation if set.
     *
     * @return string|null
     */
    public function getReasonCode(): ?string
    {
        return $this->reasonCode;
    }

    /**
     * Sets the reason code of the cancellation.
     *
     * @param string|null $reasonCode
     *
     * @return Cancellation
     */
    public function setReasonCode(?string $reasonCode): Cancellation
    {
        if (in_array($reasonCode, array_merge(CancelReasonCodes::REASON_CODE_ARRAY, [null]), true)) {
            $this->reasonCode = $reasonCode;
        }
        return $this;
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
    public function setPaymentReference(?string $paymentReference): Cancellation
    {
        $this->paymentReference = $paymentReference;
        return $this;
    }

    /**
     * Returns the net value of the amount to be cancelled.
     * This is needed for Installment Secured payment types only.
     *
     * @return float|null
     */
    public function getAmountNet(): ?float
    {
        return $this->amountNet;
    }

    /**
     * Sets the net value of the amount to be cancelled.
     * This is needed for Installment Secured payment types only.
     *
     * @param float|null $amountNet The net value of the amount to be cancelled (Installment Secured only).
     *
     * @return Cancellation The resulting cancellation object.
     */
    public function setAmountNet(?float $amountNet): Cancellation
    {
        $this->amountNet = $amountNet;
        return $this;
    }

    /**
     * Returns the vat value of the cancellation amount.
     * This is needed for Installment Secured payment types only.
     *
     * @return float|null
     */
    public function getAmountVat(): ?float
    {
        return $this->amountVat;
    }

    /**
     * Sets the vat value of the cancellation amount.
     * This is needed for Installment Secured payment types only.
     *
     * @param float|null $amountVat
     *
     * @return Cancellation
     */
    public function setAmountVat(?float $amountVat): Cancellation
    {
        $this->amountVat = $amountVat;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function expose()
    {
        $exposeArray = parent::expose();
        $payment = $this->getPayment();
        if (isset($exposeArray['amount'])
            && $payment instanceof Payment && $payment->getPaymentType() instanceof InstallmentSecured) {
            $exposeArray['amountGross'] = $exposeArray['amount'];
            unset($exposeArray['amount']);
        }
        return $exposeArray;
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'cancels';
    }
}
