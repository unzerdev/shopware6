<?php

namespace HeidelPayment\Services\Heidelpay\Webhooks;

use function array_key_exists;
use HeidelPayment\Services\Heidelpay\Webhooks\Handlers\WebhookHandlerInterface;

class WebhookRegistry implements WebhookFactoryInterface
{
    /** @var array<WebhookHandlerInterface> */
    private $webhooks;

    /**
     * {@inheritdoc}
     */
    public function getWebhookHandlers(string $event): array
    {
        if (array_key_exists($event, $this->webhooks)) {
            return $this->webhooks[$event];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function addWebhookHandler(WebhookHandlerInterface $webhookHandler, string $event): void
    {
        $this->webhooks[$event][] = $webhookHandler;
    }
}
