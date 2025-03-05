<?php

namespace UnzerSDK\Apis;

use UnzerSDK\Apis\Constants\AuthorizationMethods;


/**
 * Config for Payment API (PAPI).
 */
class PaymentApiConfig implements ApiConfig
{
    private const DOMAIN = 'api.unzer.com';
    private const TEST_DOMAIN = 'sbx-api.unzer.com';
    private const INT_DOMAIN = 'stg-api.unzer.com';

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
        return AuthorizationMethods::BASIC;
    }
}
