<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\ConfigReader;

use HeidelPayment6\Components\Struct\Configuration;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigReader implements ConfigReaderInterface
{
    /** @var string */
    public const SYSTEM_CONFIG_DOMAIN = 'HeidelPayment6.settings.';

    public const CONFIG_KEY_PUBLIC_KEY       = 'publicKey';
    public const CONFIG_KEY_PRIVATE_KEY      = 'privateKey';
    public const CONFIG_KEY_TEST_MODE        = 'testMode';
    public const CONFIG_KEY_EXTENDED_LOGGING = 'extendedLogging';
    public const CONFIG_KEY_BOOKINMODE_CARD  = 'bookingModeCreditCard';
    public const CONFIG_KEY_REGISTER_CARD    = 'registerCreditCard';
    public const CONFIG_KEY_SHIPPING_STATUS  = 'statusForAutomaticShippingNotification';

    /** @var SystemConfigService */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function read(string $salesChannelId = '', bool $fallback = true): Configuration
    {
        $values = $this->systemConfigService->getDomain(
            self::SYSTEM_CONFIG_DOMAIN,
            $salesChannelId,
            $fallback
        );

        $config = [];

        foreach ($values as $key => $value) {
            $property = substr($key, strlen(self::SYSTEM_CONFIG_DOMAIN));

            if ($property) {
                /** @var string $property */
                $config[$property] = $value;
            }
        }

        return new Configuration($config);
    }
}
