<?php

namespace UnzerSDK\Resources;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Constants\WebhookEvents;
use RuntimeException;
use stdClass;

use function in_array;

/**
 * This class represents a group of Webhooks.
 * It is a pseudo resource used to manage bulk operations on webhooks.
 * It will never receive an id from the API.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Webhooks extends AbstractUnzerResource
{
    /** @var string $url */
    protected $url;

    /** @var array $eventList */
    protected $eventList = [];

    /** @var array $webhooks */
    private $webhooks = [];

    /**
     * Webhook constructor.
     *
     * @param string $url
     * @param array  $eventList
     */
    public function __construct(string $url = '', array $eventList = [])
    {
        $this->url = $url;
        $this->eventList = $eventList;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return Webhooks
     */
    public function setUrl(string $url): Webhooks
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return array
     */
    public function getEventList(): array
    {
        return $this->eventList;
    }

    /**
     * @param string $event
     *
     * @return Webhooks
     */
    public function addEvent(string $event): Webhooks
    {
        if (in_array($event, WebhookEvents::ALLOWED_WEBHOOKS, true) && !in_array($event, $this->eventList, true)) {
            $this->eventList[] = $event;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getWebhookList(): array
    {
        return $this->webhooks;
    }

    /**
     * @param stdClass $response
     * @param string   $method
     *
     * @throws RuntimeException
     */
    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        parent::handleResponse($response, $method);

        // there are multiple events in the response
        if (isset($response->events)) {
            $this->handleRegisteredWebhooks($response->events);
        }

        // it is only one event in the response
        if (isset($response->event)) {
            $this->handleRegisteredWebhooks([$response]);
        }
    }

    /**
     * Handles the given event array
     *
     * @param array $responseArray
     *
     * @throws RuntimeException
     */
    private function handleRegisteredWebhooks(array $responseArray = []): void
    {
        $registeredWebhooks = [];

        foreach ($responseArray as $event) {
            $webhook = new Webhook();
            $webhook->setParentResource($this->getUnzerObject());
            $webhook->handleResponse($event);
            $registeredWebhooks[] = $webhook;
        }

        $this->webhooks = $registeredWebhooks;
    }
}
