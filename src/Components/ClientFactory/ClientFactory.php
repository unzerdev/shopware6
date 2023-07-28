<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ClientFactory;

use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerSDK\Interfaces\DebugHandlerInterface;
use UnzerSDK\Unzer;

class ClientFactory implements ClientFactoryInterface
{
    private ConfigReaderInterface $configReader;

    private DebugHandlerInterface $debugHandler;

    public function __construct(ConfigReaderInterface $configReader, DebugHandlerInterface $debugHandler)
    {
        $this->configReader = $configReader;
        $this->debugHandler = $debugHandler;
    }

    public function createClient(string $salesChannelId = '', string $locale = self::DEFAULT_LOCALE): Unzer
    {
        $config = $this->configReader->read($salesChannelId);

        $client = new Unzer($config->get(ConfigReader::CONFIG_KEY_PRIVATE_KEY), $locale);
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
