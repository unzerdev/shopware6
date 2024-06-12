<?php
/**
 * This trait adds the trace id to a class.
 * It can be sent to the support when a problem occurs.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

trait HasTraceId
{
    /** @var string $traceId */
    private $traceId;

    /**
     * @return string|null
     */
    public function getTraceId(): ?string
    {
        return $this->traceId;
    }

    /**
     * @param string $traceId
     *
     * @return $this
     */
    protected function setTraceId(string $traceId): self
    {
        $this->traceId = $traceId;
        return $this;
    }
}
