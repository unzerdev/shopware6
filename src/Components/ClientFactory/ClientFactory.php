<?php

declare(strict_types=1);

namespace HeidelPayment\Components\ClientFactory;

use HeidelPayment\Components\ConfigReader\ConfigReaderInterface;
use heidelpayPHP\Heidelpay;

class ClientFactory implements ClientFactoryInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(ConfigReaderInterface $configReader)
    {
        $this->configReader = $configReader;
    }

    public function createClient(string $salesChannelId = '', string $locale = self::DEFAULT_LOCALE): Heidelpay
    {
        $config = $this->configReader->read($salesChannelId);

        return new Heidelpay($config->get('privateKey'), $locale);
    }
}
