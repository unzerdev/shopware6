<?php

namespace UnzerSDK\Apis;

use UnzerSDK\Apis\Constants\AuthorizationMethods;


/**
 * Config for Payment API (PAPI) with bearer authentication.
 */
class PaymentApiConfigBearerAuth extends PaymentApiConfig
{
    public static function getAuthorizationMethod(): string
    {
        return AuthorizationMethods::BEARER;
    }
}
