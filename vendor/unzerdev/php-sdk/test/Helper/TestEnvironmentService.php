<?php
/*
 *  Helper class to manage environment variables for testing.
 *
 *  @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\Helper;

use UnzerSDK\Services\EnvironmentService;

class TestEnvironmentService extends EnvironmentService
{
    /** Primary testing Keypair used as default for most payment types. */
    public const ENV_VAR_TEST_PRIVATE_KEY_DEFAULT = 'UNZER_PAPI_TEST_PRIVATE_KEY_DEFAULT';
    public const ENV_VAR_TEST_PUBLIC_KEY_DEFAULT = 'UNZER_PAPI_TEST_PUBLIC_KEY_DEFAULT';

    /** Secondary keypair mainly used for payment methods that need a second configuration to be tested. */
    public const ENV_VAR_TEST_PRIVATE_KEY_ALTERNATIVE = 'UNZER_PAPI_TEST_PRIVATE_KEY_ALTERNATIVE';
    public const ENV_VAR_TEST_PUBLIC_KEY_ALTERNATIVE = 'UNZER_PAPI_TEST_PUBLIC_KEY_ALTERNATIVE';

    /** Third keypair mainly used for deprecated payment methods. */
    public const ENV_VAR_TEST_PRIVATE_KEY_LEGACY = 'UNZER_PAPI_TEST_PRIVATE_KEY_LEGACY';
    public const ENV_VAR_TEST_PUBLIC_KEY_LEGACY = 'UNZER_PAPI_TEST_PUBLIC_KEY_LEGACY';

    public const ENV_VAR_TEST_APPLE_MERCHANT_ID_FOLDER = 'UNZER_APPLE_MERCHANT_ID_PATH';
    public const ENV_VAR_TEST_APPLE_CA_CERTIFICATE = 'UNZER_APPLE_CA_CERTIFICATE_PATH';
    public const ENV_VAR_NAME_VERBOSE_TEST_LOGGING = 'UNZER_PAPI_VERBOSE_TEST_LOGGING';

    /**
     * Returns the CA certificate path set via environment variable.
     *
     * @return string
     */
    public static function getAppleCaCertificatePath(): string
    {
        return stripslashes($_SERVER[self::ENV_VAR_TEST_APPLE_CA_CERTIFICATE] ?? '');
    }

    /**
     * Returns the path to apple merchant ID folder set via environment variable.
     *
     * @return string
     */
    public static function getAppleMerchantIdPath(): string
    {
        return stripslashes($_SERVER[self::ENV_VAR_TEST_APPLE_MERCHANT_ID_FOLDER] ?? '');
    }

    /**
     * Returns false if the logging in tests is deactivated by environment variable.
     *
     * @return bool
     */
    public static function isTestLoggingActive(): bool
    {
        return EnvironmentService::getBoolEnvValue(self::ENV_VAR_NAME_VERBOSE_TEST_LOGGING);
    }

    /**
     * Returns the public key string set via environment variable.
     * Returns the non 3ds version of the key if the non3ds flag is set.
     * Returns an empty string if the environment variable is not set.
     *
     * @param bool $non3ds
     *
     * @return string
     */
    public static function getTestPublicKey(bool $non3ds = false): string
    {
        $variableName = $non3ds ? self::ENV_VAR_TEST_PUBLIC_KEY_ALTERNATIVE : self::ENV_VAR_TEST_PUBLIC_KEY_DEFAULT;
        $key = stripslashes($_SERVER[$variableName] ?? '');
        return empty($key) ? '' : $key;
    }

    /**
     * Returns the public key containing legacy payment methods.
     *
     * @param bool $non3ds
     *
     * @return string
     */
    public static function getLegacyTestPublicKey(): string
    {
        return stripslashes($_SERVER[self::ENV_VAR_TEST_PUBLIC_KEY_LEGACY] ?? '');
    }

    /**
     * Returns the private key string set via environment variable.
     * Returns the non 3ds version of the key if the non3ds flag is set.
     * Returns an empty string if the environment variable is not set.
     *
     * @param bool $non3ds
     *
     * @return string
     */
    public static function getTestPrivateKey(bool $non3ds = false): string
    {
        $variableName = $non3ds ? self::ENV_VAR_TEST_PRIVATE_KEY_ALTERNATIVE : self::ENV_VAR_TEST_PRIVATE_KEY_DEFAULT;
        $key = stripslashes($_SERVER[$variableName] ?? '');
        return empty($key) ? '' : $key;
    }

    public static function getLegacyTestPrivateKey(): string
    {
        return stripslashes($_SERVER[self::ENV_VAR_TEST_PRIVATE_KEY_LEGACY] ?? '');
    }
}
