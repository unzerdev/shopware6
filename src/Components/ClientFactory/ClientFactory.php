<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ClientFactory;

use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\ConfigReader\PaylaterKeyPairConfigReader;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerSDK\Interfaces\DebugHandlerInterface;
use UnzerSDK\Unzer;

class ClientFactory implements ClientFactoryInterface
{
    private ConfigReaderInterface $configReader;

    private DebugHandlerInterface $debugHandler;

    private PaylaterKeyPairConfigReader $publicKeyConfigReader;

    public function __construct(ConfigReaderInterface $configReader, DebugHandlerInterface $debugHandler, PaylaterKeyPairConfigReader $publicKeyConfigReader)
    {
        $this->configReader          = $configReader;
        $this->debugHandler          = $debugHandler;
        $this->publicKeyConfigReader = $publicKeyConfigReader;
    }

    public function createClient(KeyPairContext $keyPairContext, string $locale = self::DEFAULT_LOCALE): Unzer
    {
        $config = $this->configReader->read($keyPairContext->getSalesChannelId());

        $client = new Unzer($this->publicKeyConfigReader->getPrivateKey($keyPairContext), $locale);
        $client->setDebugMode((bool) $config->get(ConfigReader::CONFIG_KEY_EXTENDED_LOGGING));
        $client->setDebugHandler($this->debugHandler);

        return $client;
    }

    public function createClientFromPrivateKey(string $privateKey, string $locale = self::DEFAULT_LOCALE): Unzer
    {
        $config = $this->configReader->read();

        $client = new Unzer($privateKey, $locale);
        $client->setDebugMode((bool) $config->get(ConfigReader::CONFIG_KEY_EXTENDED_LOGGING));
        $client->setDebugHandler($this->debugHandler);

        return $client;
    }
}
