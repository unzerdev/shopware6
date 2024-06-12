<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This test is verifying that the set environment variables will lead to the correct configuration.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Services;

use PHPUnit\Framework\TestCase;
use UnzerSDK\test\Helper\TestEnvironmentService;

class EnvironmentServiceTest extends TestCase
{
    //<editor-fold desc="Tests">

    /**
     * Verify test logging environment vars are correctly interpreted.
     *
     * @test
     *
     * @dataProvider envVarsShouldBeInterpretedAsExpectedDP
     *
     * @param mixed $verboseLog
     * @param bool  $expectedLogEnabled
     */
    public function envVarsShouldBeInterpretedAsExpected($verboseLog, $expectedLogEnabled): void
    {
        unset(
            $_SERVER[TestEnvironmentService::ENV_VAR_NAME_VERBOSE_TEST_LOGGING]
        );

        if ($verboseLog !== null) {
            $_SERVER[TestEnvironmentService::ENV_VAR_NAME_VERBOSE_TEST_LOGGING] = $verboseLog;
        }

        $this->assertEquals($expectedLogEnabled, TestEnvironmentService::isTestLoggingActive());
    }

    /**
     * Verify string is returned if the private test key environment variable is not set.
     *
     * @test
     *
     * @dataProvider keyStringIsReturnedCorrectlyDP
     *
     * @param string  $keyEnvVar
     * @param string  $non3dsKeyEnvVar
     * @param boolean $non3ds
     * @param string  $expected
     */
    public function privateKeyStringIsReturnedCorrectly($keyEnvVar, $non3dsKeyEnvVar, $non3ds, $expected): void
    {
        unset(
            $_SERVER[TestEnvironmentService::ENV_VAR_TEST_PRIVATE_KEY_DEFAULT],
            $_SERVER[TestEnvironmentService::ENV_VAR_TEST_PRIVATE_KEY_ALTERNATIVE]
        );

        if ($keyEnvVar !== null) {
            $_SERVER[TestEnvironmentService::ENV_VAR_TEST_PRIVATE_KEY_DEFAULT] = $keyEnvVar;
        }

        if ($non3dsKeyEnvVar !== null) {
            $_SERVER[TestEnvironmentService::ENV_VAR_TEST_PRIVATE_KEY_ALTERNATIVE] = $non3dsKeyEnvVar;
        }

        $this->assertEquals($expected, TestEnvironmentService::getTestPrivateKey($non3ds));
    }

    /**
     * Verify string is returned if the public test key environment variable is not set.
     *
     * @test
     *
     * @dataProvider keyStringIsReturnedCorrectlyDP
     *
     * @param string  $keyEnvVar
     * @param string  $non3dsKeyEnvVar
     * @param boolean $non3ds
     * @param string  $expected
     */
    public function publicKeyStringIsReturnedCorrectly($keyEnvVar, $non3dsKeyEnvVar, $non3ds, $expected): void
    {
        unset(
            $_SERVER[TestEnvironmentService::ENV_VAR_TEST_PUBLIC_KEY_DEFAULT],
            $_SERVER[TestEnvironmentService::ENV_VAR_TEST_PUBLIC_KEY_ALTERNATIVE]
        );

        if ($keyEnvVar !== null) {
            $_SERVER[TestEnvironmentService::ENV_VAR_TEST_PUBLIC_KEY_DEFAULT] = $keyEnvVar;
        }

        if ($non3dsKeyEnvVar !== null) {
            $_SERVER[TestEnvironmentService::ENV_VAR_TEST_PUBLIC_KEY_ALTERNATIVE] = $non3dsKeyEnvVar;
        }

        $this->assertEquals($expected, TestEnvironmentService::getTestPublicKey($non3ds));
    }

    //</editor-fold>

    //<editor-fold desc="Data Providers">

    /**
     * Data provider for envVarsShouldBeInterpretedAsExpected.
     *
     * @return array
     */
    public function envVarsShouldBeInterpretedAsExpectedDP(): array
    {
        return [
            '#0' =>     [null, false],
            '#1' =>     [0, false],
            '#2' =>     [1, true],
            '#3' =>     [false, false],
            '#4' =>     [true, true],
            '#5' =>     ["false", false],
            '#6' =>     ["true", true],
            '#7' =>     ['fals', false],
            '#8' =>     ['tru', false],
            '#9' =>     [010, false],
            '#10' =>    ['1', true],
            '#11' =>    ['100', false],
            '#12' =>    ['0', false],
        ];
    }

    /**
     * Data provider for privateKeyStringIsReturnedCorrectly and publicKeyStringIsReturnedCorrectly.
     *
     * @return array
     */
    public function keyStringIsReturnedCorrectlyDP(): array
    {
        return [
            'expect empty string for 3ds' => [null, null, false, ''],
            'expect empty string for non 3ds' => [null, null, true, ''],
            'expect string from 3ds Env Var' => ['I am the 3ds key', 'I am the non 3ds key', false, 'I am the 3ds key'],
            'expect string from non 3ds Env Var' => ['I am the 3ds key', 'I am the non 3ds key', true, 'I am the non 3ds key']
        ];
    }

    //</editor-fold>
}
