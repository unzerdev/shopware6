<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\WebhookRegistrator;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

interface WebhookRegistratorInterface
{
    public function registerWebhook(RequestDataBag $salesChannelDomains): array;

    public function clearWebhooks(RequestDataBag $salesChannelDomains): array;
}
