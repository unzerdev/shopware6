<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\ClientFactory;

use HeidelPayment6\Components\ConfigReader\ConfigReaderInterface;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Interfaces\DebugHandlerInterface;

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

        $client = new Heidelpay($config->get('privateKey'), $locale);
        $client->setDebugMode((bool) $config->get('extendedLogging'));
        $client->setDebugHandler($this->debugHandler);

        return $client;
    }
}
