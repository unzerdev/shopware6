<?php

namespace UnzerSDK\Resources\EmbeddedResources;

use UnzerSDK\Resources\AbstractUnzerResource;

/**
 * Represents the Applepay header resource.
 *
 * @link  https://docs.unzer.com/
 *
 */
class ApplePayHeader extends AbstractUnzerResource
{
    /** @var string|null */
    protected $ephemeralPublicKey;

    /** @var string|null */
    protected $publicKeyHash;

    /** @var string|null */
    protected $transactionId;

    /**
     * ApplePayHeader constructor.
     *
     * @param string|null $ephemeralPublicKey
     * @param string|null $publicKeyHash
     * @param string|null $transactionId
     */
    public function __construct(?string $ephemeralPublicKey, ?string $publicKeyHash, ?string $transactionId = null)
    {
        $this->ephemeralPublicKey = $ephemeralPublicKey;
        $this->publicKeyHash = $publicKeyHash;
        $this->transactionId = $transactionId;
    }

    /**
     * @param string|null $ephemeralPublicKey
     *
     * @return ApplePayHeader
     */
    public function setEphemeralPublicKey(?string $ephemeralPublicKey): ApplePayHeader
    {
        $this->ephemeralPublicKey = $ephemeralPublicKey;
        return $this;
    }

    /**
     * @param string|null $publicKeyHash
     *
     * @return ApplePayHeader
     */
    public function setPublicKeyHash(?string $publicKeyHash): ApplePayHeader
    {
        $this->publicKeyHash = $publicKeyHash;
        return $this;
    }

    /**
     * @param string|null $transactionId
     *
     * @return ApplePayHeader
     */
    public function setTransactionId(?string $transactionId): ApplePayHeader
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEphemeralPublicKey(): ?string
    {
        return $this->ephemeralPublicKey;
    }

    /**
     * @return string|null
     */
    public function getPublicKeyHash(): ?string
    {
        return $this->publicKeyHash;
    }

    /**
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }
}
