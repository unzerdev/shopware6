<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\WebhookRegistrator;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Throwable;

interface WebhookRegistratorInterface
{
    /**
     * @throws HeidelpayApiException
     * @throws Throwable
     */
    public function registerWebhook(RequestDataBag $salesChannelDomains): array;

    /**
     * @throws HeidelpayApiException
     * @throws Throwable
     */
    public function clearWebhooks(RequestDataBag $salesChannelDomains): array;
}
