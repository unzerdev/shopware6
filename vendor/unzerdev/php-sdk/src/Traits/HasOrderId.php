<?php
/**
 * This trait adds the orderId property to a class.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

trait HasOrderId
{
    /** @var string $orderId */
    protected $orderId;

    /**
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    /**
     * @param string|null $orderId
     *
     * @return $this
     */
    public function setOrderId(?string $orderId): self
    {
        $this->orderId = $orderId;
        return $this;
    }
}
