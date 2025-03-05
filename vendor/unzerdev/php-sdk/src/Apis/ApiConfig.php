<?php

namespace UnzerSDK\Apis;

/**
 * ApiConfig provides configuration data for a given API.
 */
interface ApiConfig
{
    public static function getDomain(): string;

    public static function getTestDomain(): string;

    public static function getIntegrationDomain(): string;

    public static function getAuthorizationMethod(): string;
}
