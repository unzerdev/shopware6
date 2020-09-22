<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ClientFactory;

use heidelpayPHP\Heidelpay;
use heidelpayPHP\Interfaces\DebugHandlerInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;

class ClientFactory implements ClientFactoryInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var DebugHandlerInterface */
    private $debugHandler;

    public function __construct(ConfigReaderInterface $configReader, DebugHandlerInterface $debugHandler)
    {
        $this->configReader = $configReader;
        $this->debugHandler = $debugHandler;
    }

    public function createClient(string $salesChannelId = '', string $locale = self::DEFAULT_LOCALE): Heidelpay
    {
        $config = $this->configReader->read($salesChannelId);

        $client = new Heidelpay($config->get(ConfigReader::CONFIG_KEY_PRIVATE_KEY), $locale);
        $client->setDebugMode((bool) $config->get(ConfigReader::CONFIG_KEY_EXTENDED_LOGGING));
        $client->setDebugHandler($this->debugHandler);

        return $client;
    }

    public function createClientFromPrivateKey(string $privateKey, string $locale = self::DEFAULT_LOCALE): Heidelpay
    {
        $config = $this->configReader->read();

        $client = new Heidelpay($privateKey, $locale);
        $client->setDebugMode((bool) $config->get(ConfigReader::CONFIG_KEY_EXTENDED_LOGGING));
        $client->setDebugHandler($this->debugHandler);

        return $client;
    }
}
