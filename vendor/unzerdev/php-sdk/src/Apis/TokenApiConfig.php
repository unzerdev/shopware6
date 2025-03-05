<?php

namespace UnzerSDK\Apis;

use UnzerSDK\Apis\Constants\AuthorizationMethods;

/**
 * Config for Token Service API.
 */
class TokenApiConfig implements ApiConfig
{
    private const DOMAIN = 'token.upcgw.com';
    private const TEST_DOMAIN = 'token.test.upcgw.com';
    private const INT_DOMAIN = 'token.int.unzer.io';

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
