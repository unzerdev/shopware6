<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ClientFactory;

use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\ConfigReader\KeyPairConfigReader;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerSDK\Interfaces\DebugHandlerInterface;
use UnzerSDK\Unzer;

class ClientFactory implements ClientFactoryInterface
{
    private ConfigReaderInterface $configReader;

    private DebugHandlerInterface $debugHandler;

    private KeyPairConfigReader $keyPairConfigReader;

    public function __construct(ConfigReaderInterface $configReader, DebugHandlerInterface $debugHandler, KeyPairConfigReader $keyPairConfigReader)
    {
        $this->configReader = $configReader;
        $this->debugHandler = $debugHandler;
        $this->keyPairConfigReader = $keyPairConfigReader;
    }

    public function createClient(KeyPairContext $keyPairContext, string $locale = self::DEFAULT_LOCALE): Unzer
    {
        $client = new Unzer($this->keyPairConfigReader->getPrivateKey($keyPairContext), $locale);
        $this->applyGlobalClientSettings($client, $keyPairContext->getSalesChannelId());

        return $client;
    }

    public function createClientFromPrivateKey(string $privateKey, string $salesChannelId = '', string $locale = self::DEFAULT_LOCALE): Unzer
    {
        $client = new Unzer($privateKey, $locale);
        $this->applyGlobalClientSettings($client, $salesChannelId);

        return $client;
    }

    public function createClientFromPublicKey(string $publicKey, string $salesChannelId = '', string $locale = self::DEFAULT_LOCALE): Unzer
    {
        $privateKey = $this->keyPairConfigReader->getMatchingKey($publicKey, $salesChannelId);

        $client = new Unzer($privateKey, $locale);
        $this->applyGlobalClientSettings($client, $salesChannelId);

        return $client;
    }

    protected function applyGlobalClientSettings(Unzer $client, string $salesChannelId = '')
    {
        $config = $this->configReader->read($salesChannelId);
        $client->setDebugMode((bool)$config->get(ConfigReader::CONFIG_KEY_EXTENDED_LOGGING));
        $client->setDebugHandler($this->debugHandler);
        $client->setClientIp($_SERVER['REMOTE_ADDR'] ?? null);
    }
}
