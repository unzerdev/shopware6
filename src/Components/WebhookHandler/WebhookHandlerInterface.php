<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\WebhookHandler;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use UnzerPayment6\Components\Struct\Webhook;

interface WebhookHandlerInterface
{
    public function supports(Webhook $webhook, SalesChannelContext $context): bool;

    public function execute(Webhook $webhook, SalesChannelContext $context): void;
}
