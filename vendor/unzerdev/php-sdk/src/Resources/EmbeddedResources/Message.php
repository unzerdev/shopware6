<?php

namespace UnzerSDK\Resources\EmbeddedResources;

use UnzerSDK\Resources\AbstractUnzerResource;

/**
 * Represents the message resource holding information like a transaction error code and message.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Message extends AbstractUnzerResource
{
    /** @var string $code */
    private $code = '';

    /** @var string $customer */
    private $customer = '';

    /** @var string $merchant */
    private $merchant = '';

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return Message
     */
    protected function setCode(string $code): Message
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomer(): string
    {
        return $this->customer;
    }

    /**
     * @param string $customer
     *
     * @return Message
     */
    protected function setCustomer(string $customer): Message
    {
        $this->customer = $customer;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMerchant(): ?string
    {
        return $this->merchant;
    }

    /**
     * @param string|null $merchant
     *
     * @return Message
     */
    protected function setMerchant(?string $merchant): Message
    {
        $this->merchant = $merchant;
        return $this;
    }
}
