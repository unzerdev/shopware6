<?php

namespace UnzerSDK\Apis;

use UnzerSDK\Apis\Constants\AuthorizationMethods;

/**
 * Config for Paypage API.
 */
class PaypageAPIConfig implements ApiConfig
{
    private const DOMAIN = 'paypage.unzer.com';
    private const TEST_DOMAIN = 'paypage.test.unzer.io';
    private const INT_DOMAIN = 'paypage.int.unzer.io';

    public static function getDomain(): string
    {
        return self::DOMAIN;
    }

    public static function getIntegrationDomain(): string
    {
        return self::INT_DOMAIN;
    }

    public static function getTestDomain(): string
    {
        return self::TEST_DOMAIN;
    }

    public static function getAuthorizationMethod(): string
    {
        return AuthorizationMethods::BEARER;
    }
}
