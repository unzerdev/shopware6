<?php

namespace HeidelPayment\Services\Heidelpay\Webhooks\Handlers;

use HeidelPayment\Components\Struct\Webhook;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface WebhookHandlerInterface
{
    public function supports(Webhook $webhook, SalesChannelContext $context): bool;

    public function execute(Webhook $webhook, SalesChannelContext $context): void;
}
