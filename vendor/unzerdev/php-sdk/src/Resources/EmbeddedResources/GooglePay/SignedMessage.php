<?php

namespace UnzerSDK\Resources\EmbeddedResources\GooglePay;

use UnzerSDK\Resources\AbstractUnzerResource;

class SignedMessage extends AbstractUnzerResource
{
    /** @var string */
    protected $tag;

    /** @var string */
    protected $ephemeralPublicKey;

    /** @var string */
    protected $encryptedMessage;

    public function __construct(?string $tag = null, ?string $ephemeralPublicKey = null, ?string $encryptedMessage = null)
    {
        $this->tag = $tag;
        $this->ephemeralPublicKey = $ephemeralPublicKey;
        $this->encryptedMessage = $encryptedMessage;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): SignedMessage
    {
        $this->tag = $tag;
        return $this;
    }

    public function getEphemeralPublicKey(): string
    {
        return $this->ephemeralPublicKey;
    }

    public function setEphemeralPublicKey(string $ephemeralPublicKey): SignedMessage
    {
        $this->ephemeralPublicKey = $ephemeralPublicKey;
        return $this;
    }

    public function getEncryptedMessage(): string
    {
        return $this->encryptedMessage;
    }

    public function setEncryptedMessage(string $encryptedMessage): SignedMessage
    {
        $this->encryptedMessage = $encryptedMessage;
        return $this;
    }
}
