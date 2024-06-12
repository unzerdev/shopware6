<?php

namespace UnzerSDK\Services;

use function in_array;
use function is_bool;

/**
 * This service provides for functionalities concerning the PAPI environment.
 *
 * @link  https://docs.unzer.com/
 *
 */
class EnvironmentService
{
    private const ENV_VAR_NAME_ENVIRONMENT = 'UNZER_PAPI_ENV';
    public const ENV_VAR_VALUE_STAGING_ENVIRONMENT = 'STG';
    public const ENV_VAR_VALUE_DEVELOPMENT_ENVIRONMENT = 'DEV';
    public const ENV_VAR_VALUE_PROD_ENVIRONMENT = 'PROD';
    private const ENV_VAR_NAME_TIMEOUT = 'UNZER_PAPI_TIMEOUT';
    private const DEFAULT_TIMEOUT = 60;

    private const ENV_VAR_NAME_CURL_VERBOSE = 'UNZER_PAPI_CURL_VERBOSE';

    /**
     * Returns the value of the given env var as bool.
     *
     * @param string $varName
     *
     * @return bool
     */
    protected static function getBoolEnvValue(string $varName): bool
    {
        /** @noinspection ProperNullCoalescingOperatorUsageInspection */
        $envVar = $_SERVER[$varName] ?? false;
        if (!is_bool($envVar)) {
            $envVar = in_array(strtolower(stripslashes($envVar)), [true, 'true', '1'], true);
        }
        return $envVar;
    }

    /**
     * Returns the PAPI environment set via environment variable or PROD es default.
     *
     * @return string
     */
    public function getPapiEnvironment(): string
    {
        return stripslashes($_SERVER[self::ENV_VAR_NAME_ENVIRONMENT] ?? self::ENV_VAR_VALUE_PROD_ENVIRONMENT);
    }

    /**
     * Returns the timeout set via environment variable or the default timeout.
     * ATTENTION: Setting this value to 0 will disable the limit.
     *
     * @return int
     */
    public static function getTimeout(): int
    {
        $timeout = stripslashes($_SERVER[self::ENV_VAR_NAME_TIMEOUT] ?? '');
        return is_numeric($timeout) ? (int)$timeout : self::DEFAULT_TIMEOUT;
    }

    /**
     * Returns the curl verbose flag.
     *
     * @return bool
     */
    public static function isCurlVerbose(): bool
    {
        $curlVerbose = strtolower(stripslashes($_SERVER[self::ENV_VAR_NAME_CURL_VERBOSE] ?? 'false'));
        return in_array($curlVerbose, ['true', '1'], true);
    }
}
