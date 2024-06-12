<?php

namespace UnzerSDK\Resources;

use UnzerSDK\Adapter\HttpAdapterInterface;

/**
 * This represents the Webhook resource.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Webhook extends AbstractUnzerResource
{
    /** @var string $url */
    protected $url;

    /** @var string $event */
    protected $event;

    /**
     * Webhook constructor.
     *
     * @param string $url
     * @param string $event
     */
    public function __construct(string $url = '', string $event = '')
    {
        $this->url = $url;
        $this->event = $event;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return Webhook
     */
    public function setUrl(string $url): Webhook
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEvent(): ?string
    {
        return $this->event;
    }

    /**
     * @param string $event
     *
     * @return Webhook
     */
    public function setEvent(string $event): Webhook
    {
        $this->event = $event;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'webhooks';
    }
}
