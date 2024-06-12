<?php

namespace UnzerSDK\Resources\TransactionTypes;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Traits\HasAccountInformation;
use UnzerSDK\Traits\HasCancellations;
use UnzerSDK\Traits\HasChargebacks;
use UnzerSDK\Traits\HasDescriptor;
use UnzerSDK\Traits\HasRecurrenceType;
use RuntimeException;

/**
 * This represents the charge transaction.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Charge extends AbstractTransactionType
{
    use HasCancellations;
    use HasRecurrenceType;
    use HasAccountInformation;
    use HasDescriptor;
    use HasChargebacks;

    /** @var float $amount */
    protected $amount;

    /** @var string $currency */
    protected $currency;

    /** @var string $returnUrl */
    protected $returnUrl;

    /** @var string $paymentReference */
    protected $paymentReference;

    /** @var bool $card3ds */
    protected $card3ds;

    /**
     * Authorization constructor.
     *
     * @param float|null  $amount
     * @param string|null $currency
     * @param string|null $returnUrl
     */
    public function __construct(float $amount = null, string $currency = null, string $returnUrl = null)
    {
        $this->setAmount($amount);
        $this->setCurrency($currency);
        $this->setReturnUrl($returnUrl);
    }

    /**
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * @param float|null $amount
     *
     * @return self
     */
    public function setAmount(?float $amount): self
    {
        $this->amount = $amount !== null ? round($amount, 4) : null;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getCancelledAmount(): ?float
    {
        $amount = 0.0;
        foreach ($this->getCancellations() as $cancellation) {
            /** @var Cancellation $cancellation */
            if ($cancellation->isSuccess()) {
                $amount += $cancellation->getAmount();
            }
        }

        return $amount;
    }

    /**
     * @return float|null
     */
    public function getTotalAmount(): ?float
    {
        return round($this->getAmount() - $this->getCancelledAmount(), 4);
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string|null $currency
     *
     * @return self
     */
    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    /**
     * @param string|null $returnUrl
     *
     * @return self
     */
    public function setReturnUrl(?string $returnUrl): self
    {
        $this->returnUrl = $returnUrl;
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
     * @param string|null $referenceText
     *
     * @return Charge
     */
    public function setPaymentReference(?string $referenceText): Charge
    {
        $this->paymentReference = $referenceText;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isCard3ds(): ?bool
    {
        return $this->card3ds;
    }

    /**
     * @param bool|null $card3ds
     *
     * @return Charge
     */
    public function setCard3ds(?bool $card3ds): Charge
    {
        $this->card3ds = $card3ds;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'charges';
    }

    /**
     * Full cancel of this authorization.
     * Returns the last cancellation object if charge is already canceled.
     * Creates and returns new cancellation object otherwise.
     *
     * @param float|null  $amount           The amount to be canceled.
     *                                      This will be sent as amountGross in case of Installment Secured payment method.
     * @param string|null $reasonCode       Reason for the Cancellation ref \UnzerSDK\Constants\CancelReasonCodes.
     * @param string|null $paymentReference A reference string for the payment.
     * @param float|null  $amountNet        The net value of the amount to be cancelled (Installment Secured only).
     * @param float|null  $amountVat        The vat value of the amount to be cancelled (Installment Secured only).
     *
     * @return Cancellation The resulting Cancellation object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function cancel(
        float  $amount = null,
        string $reasonCode = null,
        string $paymentReference = null,
        float  $amountNet = null,
        float  $amountVat = null
    ): Cancellation {
        return $this->getUnzerObject()->cancelCharge(
            $this,
            $amount,
            $reasonCode,
            $paymentReference,
            $amountNet,
            $amountVat
        );
    }
}
