<?php

namespace UnzerSDK\Services;

use UnzerSDK\Unzer;
use UnzerSDK\Interfaces\ResourceServiceInterface;
use UnzerSDK\Interfaces\WebhookServiceInterface;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Webhook;
use UnzerSDK\Resources\Webhooks;
use RuntimeException;

use function is_string;

/**
 * This service provides methods to manage webhooks/events.
 *
 * @link  https://docs.unzer.com/
 *
 */
class WebhookService implements WebhookServiceInterface
{
    /** @var Unzer $unzer */
    private $unzer;

    /** @var ResourceServiceInterface $resourceService */
    private $resourceService;

    /**
     * PaymentService constructor.
     *
     * @param Unzer $unzer
     */
    public function __construct(Unzer $unzer)
    {
        $this->unzer = $unzer;
        $this->resourceService = $unzer->getResourceService();
    }

    /**
     * @return Unzer
     */
    public function getUnzer(): Unzer
    {
        return $this->unzer;
    }

    /**
     * @param Unzer $unzer
     *
     * @return WebhookService
     */
    public function setUnzer(Unzer $unzer): WebhookService
    {
        $this->unzer = $unzer;
        return $this;
    }

    /**
     * @return ResourceServiceInterface
     */
    public function getResourceService(): ResourceServiceInterface
    {
        return $this->resourceService;
    }

    /**
     * @param ResourceServiceInterface $resourceService
     *
     * @return WebhookService
     */
    public function setResourceService(ResourceServiceInterface $resourceService): WebhookService
    {
        $this->resourceService = $resourceService;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function createWebhook(string $url, string $event): Webhook
    {
        $webhook = new Webhook($url, $event);
        $webhook->setParentResource($this->unzer);
        $this->resourceService->createResource($webhook);
        return $webhook;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchWebhook($webhook): Webhook
    {
        $webhookObject = $webhook;
        if (is_string($webhook)) {
            $webhookObject = new Webhook();
            $webhookObject->setId($webhook);
        }

        $webhookObject->setParentResource($this->unzer);
        $this->resourceService->fetchResource($webhookObject);
        return $webhookObject;
    }

    /**
     * {@inheritDoc}
     */
    public function updateWebhook(Webhook $webhook): Webhook
    {
        $webhook->setParentResource($this->unzer);
        $this->resourceService->updateResource($webhook);
        return $webhook;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteWebhook($webhook)
    {
        $webhookObject = $webhook;

        if (is_string($webhook)) {
            $webhookObject = $this->fetchWebhook($webhook);
        }

        return $this->resourceService->deleteResource($webhookObject);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllWebhooks(): array
    {
        $webhooks = new Webhooks();
        $webhooks->setParentResource($this->unzer);
        /** @var Webhooks $webhooks */
        $webhooks = $this->resourceService->fetchResource($webhooks);

        return $webhooks->getWebhookList();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAllWebhooks(): void
    {
        $webhooks = new Webhooks();
        $webhooks->setParentResource($this->unzer);
        $this->resourceService->deleteResource($webhooks);
    }

    /**
     * {@inheritDoc}
     */
    public function registerMultipleWebhooks(string $url, array $events): array
    {
        $webhooks = new Webhooks($url, $events);
        $webhooks->setParentResource($this->unzer);
        /** @var Webhooks $webhooks */
        $webhooks = $this->resourceService->createResource($webhooks);

        return $webhooks->getWebhookList();
    }

    /**
     * {@inheritDoc}
     */
    public function fetchResourceFromEvent(string $eventJson = null): AbstractUnzerResource
    {
        $resourceObject = null;
        $eventData = json_decode($eventJson ?? $this->readInputStream(), false);
        $retrieveUrl = $eventData->retrieveUrl ?? null;

        if (!empty($retrieveUrl)) {
            $this->unzer->debugLog('Received event: ' . json_encode($eventData)); // encode again to uglify json
            $resourceObject = $this->resourceService->fetchResourceByUrl($retrieveUrl);
        }

        if (!$resourceObject instanceof AbstractUnzerResource) {
            throw new RuntimeException('Error fetching resource!');
        }

        return $resourceObject;
    }

    /**
     * Read and return the input stream.
     *
     * @return false|string
     */
    public function readInputStream()
    {
        return file_get_contents('php://input');
    }
}
