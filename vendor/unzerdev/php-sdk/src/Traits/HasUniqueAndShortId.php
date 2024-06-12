<?php
/**
 * This trait adds the short id and unique id property to a class.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

trait HasUniqueAndShortId
{
    /** @var string $uniqueId */
    private $uniqueId;

    /** @var string $shortId */
    private $shortId;

    /**
     * @return string|null
     */
    public function getUniqueId(): ?string
    {
        return $this->uniqueId;
    }

    /**
     * @param string $uniqueId
     *
     * @return $this
     */
    protected function setUniqueId(string $uniqueId): self
    {
        $this->uniqueId = $uniqueId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShortId(): ?string
    {
        return $this->shortId;
    }

    /**
     * @param string $shortId
     *
     * @return self
     */
    protected function setShortId(string $shortId): self
    {
        $this->shortId = $shortId;
        return $this;
    }
}
