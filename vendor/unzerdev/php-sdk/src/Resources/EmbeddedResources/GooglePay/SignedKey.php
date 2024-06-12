<?php

namespace UnzerSDK\Resources\EmbeddedResources\GooglePay;

use UnzerSDK\Resources\AbstractUnzerResource;

class SignedKey extends AbstractUnzerResource
{
    /**
     * @var string $keyExpiration
     */
    protected $keyExpiration;

    /**
     * @var string $keyValue
     */
    protected $keyValue;

    /**
     * @param string $keyExpiration
     * @param string $keyValue
     */
    public function __construct(?string $keyExpiration = null, ?string $keyValue = null)
    {
        $this->keyExpiration = $keyExpiration;
        $this->keyValue = $keyValue;
    }

    public function getKeyExpiration(): string
    {
        return $this->keyExpiration;
    }

    public function setKeyExpiration(string $keyExpiration): SignedKey
    {
        $this->keyExpiration = $keyExpiration;
        return $this;
    }

    public function getKeyValue(): string
    {
        return $this->keyValue;
    }

    public function setKeyValue(string $keyValue): SignedKey
    {
        $this->keyValue = $keyValue;
        return $this;
    }
}
