<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\WebhookHandler;

use HeidelPayment6\Components\Struct\Webhook;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface WebhookHandlerInterface
{
    public function supports(Webhook $webhook, SalesChannelContext $context): bool;

    public function execute(Webhook $webhook, SalesChannelContext $context): void;
}
