<?php

namespace HeidelPayment\Components\ClientFactory;

use heidelpayPHP\Heidelpay;

interface ClientFactoryInterface
{
    /** @var string */
    public const DEFAULT_LOCALE = 'en_GB';

    public function createClient(string $salesChannelId = '', string $locale = self::DEFAULT_LOCALE): Heidelpay;
}
