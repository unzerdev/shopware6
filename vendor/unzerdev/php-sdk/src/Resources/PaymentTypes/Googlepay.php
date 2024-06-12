<?php

namespace UnzerSDK\Resources\PaymentTypes;

use stdClass;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\EmbeddedResources\GooglePay\IntermediateSigningKey;
use UnzerSDK\Resources\EmbeddedResources\GooglePay\SignedMessage;
use UnzerSDK\Traits\CanAuthorize;
use UnzerSDK\Traits\CanDirectCharge;

/**
 * Represents Google Pay type. It requires data from payment method token returned by Google in the `PaymentData`.
 * These data are used to create the payment type on the Unzer API.
 */
class Googlepay extends BasePaymentType
{
    use CanDirectCharge;
    use CanAuthorize;

    /**
     * @var string $protocolVersion
     */
    protected $protocolVersion;

    /**
     * @var string $signature
     */
    protected $signature;

    /**
     * @var IntermediateSigningKey $intermediateSigningKey
     */
    protected $intermediateSigningKey;

    /** @var SignedMessage $signedMessage */
    protected $signedMessage;

    /** @var string $number */
    private $number;

    /** @var string $expiryDate */
    private $expiryDate;

    /**
     * @param string                 $protocolVersion
     * @param string                 $signature
     * @param IntermediateSigningKey $intermediateSigningKey
     * @param string                 $signedMessage
     */
    public function __construct(
        ?string                 $protocolVersion = null,
        ?string                 $signature = null,
        ?IntermediateSigningKey $intermediateSigningKey = null,
        ?SignedMessage          $signedMessage = null
    ) {
        $this->protocolVersion = $protocolVersion;
        $this->signature = $signature;
        $this->intermediateSigningKey = $intermediateSigningKey;
        $this->signedMessage = $signedMessage;
    }

    public static function getResourceName(): string
    {
        return 'googlepay';
    }

    /**
     * @inheritDoc
     */
    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        parent::handleResponse($response, $method);

        if (isset($response->intermediateSigningKey)) {
            $this->intermediateSigningKey = new IntermediateSigningKey();
            $this->intermediateSigningKey->handleResponse($response->intermediateSigningKey);
        }
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    protected function setNumber(string $number): Googlepay
    {
        $this->number = $number;
        return $this;
    }

    public function getExpiryDate(): string
    {
        return $this->expiryDate;
    }

    protected function setExpiryDate(string $expiryDate): Googlepay
    {
        $this->expiryDate = $expiryDate;
        return $this;
    }

    public function getProtocolVersion(): ?string
    {
        return $this->protocolVersion;
    }

    public function setProtocolVersion(?string $protocolVersion): Googlepay
    {
        $this->protocolVersion = $protocolVersion;
        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): Googlepay
    {
        $this->signature = $signature;
        return $this;
    }

    public function getIntermediateSigningKey(): ?IntermediateSigningKey
    {
        return $this->intermediateSigningKey;
    }

    public function setIntermediateSigningKey(?IntermediateSigningKey $intermediateSigningKey): Googlepay
    {
        $this->intermediateSigningKey = $intermediateSigningKey;
        return $this;
    }

    public function getSignedMessage(): ?SignedMessage
    {
        return $this->signedMessage;
    }

    public function setSignedMessage(?SignedMessage $signedMessage): Googlepay
    {
        $this->signedMessage = $signedMessage;
        return $this;
    }
}
