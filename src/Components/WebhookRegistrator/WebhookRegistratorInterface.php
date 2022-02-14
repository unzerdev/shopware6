<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\WebhookRegistrator;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

interface WebhookRegistratorInterface
{
    public function registerWebhook(RequestDataBag $salesChannelDomains): array;

    public function clearWebhooks(string $privateKey, array $webhookIds): array;

    public function getWebhooks(string $privateKey): array;
}
