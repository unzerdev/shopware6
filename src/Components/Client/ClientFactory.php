<?php

namespace HeidelPayment\Components\Client;

use HeidelPayment\Components\ConfigReader\ConfigReaderInterface;
use heidelpayPHP\Heidelpay;

class ClientFactory
{
    /** @var string */
    private const DEFAULT_LOCALE = 'en_GB';

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(ConfigReaderInterface $configReader)
    {
        $this->configReader = $configReader;
    }

    public function createClient(string $salesChannelId = '', string $locale = self::DEFAULT_LOCALE): Heidelpay
    {
        $config = $this->configReader->read($salesChannelId);

        return new Heidelpay($config['privateKey'], $locale);
    }
}
