<?php

namespace UnzerSDK\Resources\PaymentTypes;

use stdClass;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\EmbeddedResources\ApplePayHeader;
use UnzerSDK\Traits\CanAuthorize;
use UnzerSDK\Traits\CanDirectCharge;

class Applepay extends BasePaymentType
{
    use CanDirectCharge;
    use CanAuthorize;

    /** @var string|null $applicationExpirationDate */
    private $applicationExpirationDate;

    /** @var string|null $applicationPrimaryAccountNumber */
    private $applicationPrimaryAccountNumber;

    /** @var string|null $currencyCode */
    private $currencyCode;

    /** @var string|null $data */
    protected $data;

    /** @var string|null $method */
    private $method;

    /** @var string|null $signature */
    protected $signature;

    /** @var float $transactionAmount */
    private $transactionAmount;

    /** @var string|null $version */
    protected $version;

    /** @var ApplePayHeader|null $header */
    protected $header;

    /**
     * Apple Pay constructor.
     *
     * @param string|null         $version
     * @param string|null         $data
     * @param string|null         $signature
     * @param ApplePayHeader|null $header
     */
    public function __construct(
        ?string $version,
        ?string $data,
        ?string $signature,
        ?ApplePayHeader $header
    ) {
        $this->version = $version;
        $this->data = $data;
        $this->signature = $signature;
        $this->header = $header;
    }

    /**
     * @return string|null
     */
    public function getApplicationExpirationDate(): ?string
    {
        return $this->applicationExpirationDate;
    }

    /**
     * @return string|null
     */
    public function getApplicationPrimaryAccountNumber(): ?string
    {
        return $this->applicationPrimaryAccountNumber;
    }

    /**
     * @return string|null
     */
    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    /**
     * @return string|null
     */
    public function getData(): ?string
    {
        return $this->data;
    }

    /**
     * @return ApplePayHeader|null
     */
    public function getHeader(): ?ApplePayHeader
    {
        return $this->header;
    }

    /**
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * @return string|null
     */
    public function getSignature(): ?string
    {
        return $this->signature;
    }

    /**
     * @return float
     */
    public function getTransactionAmount(): ?float
    {
        return $this->transactionAmount;
    }

    /**
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @param string|null $applicationExpirationDate
     *
     * @return $this
     */
    protected function setApplicationExpirationDate(?string $applicationExpirationDate): Applepay
    {
        $this->applicationExpirationDate = $applicationExpirationDate;
        return $this;
    }

    /**
     * @param string|null $applicationPrimaryAccountNumber
     *
     * @return $this
     */
    protected function setApplicationPrimaryAccountNumber(?string $applicationPrimaryAccountNumber): Applepay
    {
        $this->applicationPrimaryAccountNumber = $applicationPrimaryAccountNumber;
        return $this;
    }

    /**
     * @param string|null $currencyCode
     *
     * @return $this
     */
    protected function setCurrencyCode(?string $currencyCode): Applepay
    {
        $this->currencyCode = $currencyCode;
        return $this;
    }

    /**
     * @param string|null $data
     *
     * @return $this
     */
    public function setData(?string $data): Applepay
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param ApplePayHeader $header
     *
     * @return Applepay
     */
    public function setHeader(ApplePayHeader $header): Applepay
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @param string|null $method
     *
     * @return $this
     */
    protected function setMethod(?string $method): Applepay
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @param string|null $signature
     *
     * @return $this
     */
    public function setSignature(?string $signature): Applepay
    {
        $this->signature = $signature;
        return $this;
    }

    /**
     * @param float $transactionAmount
     *
     * @return $this
     */
    protected function setTransactionAmount(float $transactionAmount): Applepay
    {
        $this->transactionAmount = $transactionAmount;
        return $this;
    }

    /**
     * @param string|null $version
     *
     * @return $this
     */
    public function setVersion(?string $version): Applepay
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        parent::handleResponse($response, $method);

        if (isset($response->header)) {
            $this->header = new ApplePayHeader(null, null, null);
            $this->header->handleResponse($response->header);
        }
    }
}
