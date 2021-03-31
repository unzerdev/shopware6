<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ClientFactory;

use UnzerSDK\Unzer;

interface ClientFactoryInterface
{
    /** @var string */
    public const DEFAULT_LOCALE = 'en-GB';

    public function createClient(string $salesChannelId = '', string $locale = self::DEFAULT_LOCALE): Unzer;

    public function createClientFromPrivateKey(string $privateKey, string $locale = self::DEFAULT_LOCALE): Unzer;
}
