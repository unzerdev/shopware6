<?php

namespace UnzerSDK\Resources\TransactionTypes;

use RuntimeException;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Traits\HasAccountInformation;
use UnzerSDK\Traits\HasCancellations;
use UnzerSDK\Traits\HasDescriptor;
use UnzerSDK\Traits\HasRecurrenceType;

/**
 * This represents the authorization transaction.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Authorization extends AbstractTransactionType
{
    use HasCancellations;
    use HasRecurrenceType;
    use HasAccountInformation;
    use HasDescriptor;

    /** @var float $amount */
    protected $amount = 0.0;

    /** @var string $currency */
    protected $currency;

    /** @var string $returnUrl */
    protected $returnUrl;

    /** @var bool $card3ds */
    protected $card3ds;

    /** @var string $paymentReference */
    protected $paymentReference;

    /** @var string $externalOrderId*/
    private $externalOrderId;

    /** @var string $zgReferenceId*/
    private $zgReferenceId;

    /** @var string $PDFLink*/
    private $PDFLink;

    /**
     * Authorization constructor.
     *
     * @param float|null  $amount
     * @param string|null $currency
     * @param string|null $returnUrl
     */
    public function __construct(?float $amount = null, ?string $currency = null, ?string $returnUrl = null)
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
            $amount += $cancellation->getAmount();
        }

        return $amount;
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
     * @return bool|null
     */
    public function isCard3ds(): ?bool
    {
        return $this->card3ds;
    }

    /**
     * @param bool|null $card3ds
     *
     * @return Authorization
     */
    public function setCard3ds(?bool $card3ds): Authorization
    {
        $this->card3ds = $card3ds;
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
     * @return Authorization
     */
    public function setPaymentReference(?string $paymentReference): Authorization
    {
        $this->paymentReference = $paymentReference;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getExternalOrderId(): ?string
    {
        return $this->externalOrderId;
    }

    /**
     * @param string|null $externalOrderId
     *
     * @return Authorization
     */
    protected function setExternalOrderId(?string $externalOrderId): Authorization
    {
        $this->externalOrderId = $externalOrderId;
        return $this;
    }

    /**
     * Returns the reference ID of the insurance provider if applicable.
     *
     * @return string|null
     */
    public function getZgReferenceId(): ?string
    {
        return $this->zgReferenceId;
    }

    /**
     * Sets the reference ID of the insurance provider.
     *
     * @param string|null $zgReferenceId
     *
     * @return Authorization
     */
    protected function setZgReferenceId(?string $zgReferenceId): Authorization
    {
        $this->zgReferenceId = $zgReferenceId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPDFLink(): ?string
    {
        return $this->PDFLink;
    }

    /**
     * @param string|null $PDFLink
     *
     * @return Authorization
     */
    protected function setPDFLink(?string $PDFLink): Authorization
    {
        $this->PDFLink = $PDFLink;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'authorize';
    }

    /**
     * Full cancel of this authorization.
     *
     * @param float|null $amount
     *
     * @return Cancellation
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function cancel(?float $amount = null): Cancellation
    {
        return $this->getUnzerObject()->cancelAuthorization($this, $amount);
    }

    /**
     * Charge authorization.
     *
     * @param float|null $amount
     *
     * @return Charge
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function charge(?float $amount = null): Charge
    {
        $payment = $this->getPayment();
        if (!$payment instanceof Payment) {
            throw new RuntimeException('Payment object is missing. Try fetching the object first!');
        }
        return $this->getUnzerObject()->chargeAuthorization($payment, $amount);
    }
}
