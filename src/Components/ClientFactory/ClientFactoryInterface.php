<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ClientFactory;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerSDK\Unzer;

interface ClientFactoryInterface
{
    /**
     * @var string
     */
    public const DEFAULT_LOCALE = 'en-GB';

    public function createClient(KeyPairContext $keyPairContext, string $locale = self::DEFAULT_LOCALE): Unzer;

    public function createClientFromSalesChannelContext(SalesChannelContext $salesChannelContext, ?Request $request = null): Unzer;

    public function createClientFromSalesChannelId(?string $salesChannelId, ?Request $request = null): Unzer;

    public function createClientFromPrivateKey(string $privateKey, string $salesChannelId = '', string $locale = self::DEFAULT_LOCALE): Unzer;

    public function createClientFromPublicKey(string $publicKey, string $salesChannelId = '', string $locale = self::DEFAULT_LOCALE): Unzer;
}
