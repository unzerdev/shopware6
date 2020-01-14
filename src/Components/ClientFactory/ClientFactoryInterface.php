<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\ClientFactory;

use heidelpayPHP\Heidelpay;

interface ClientFactoryInterface
{
    /** @var string */
    public const DEFAULT_LOCALE = 'en_GB';

    public function createClient(string $salesChannelId = '', string $locale = self::DEFAULT_LOCALE): Heidelpay;

    public function createClientFromPrivateKey(string $privateKey, string $locale = self::DEFAULT_LOCALE): Heidelpay;
}
