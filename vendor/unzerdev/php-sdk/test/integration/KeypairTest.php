<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify keypair functionalities.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration;

use UnzerSDK\Unzer;
use UnzerSDK\test\BaseIntegrationTest;
use RuntimeException;

class KeypairTest extends BaseIntegrationTest
{
    /**
     * Validate valid keys are accepted.
     *
     * @test
     *
     * @dataProvider validKeysDataProvider
     *
     * @param string $key
     */
    public function validKeysShouldBeExcepted($key): void
    {
        $unzer = new Unzer($key);
        $this->assertEquals($key, $unzer->getKey());
    }

    /**
     * Validate invalid keys are revoked.
     *
     * @test
     *
     * @dataProvider invalidKeysDataProvider
     *
     * @param string $key
     */
    public function invalidKeysShouldResultInException($key): void
    {
        $this->expectException(RuntimeException::class);
        new Unzer($key);
    }

    /**
     * Verify key pair config can be fetched.
     *
     * @test
     */
    public function keypairShouldReturnExpectedValues(): void
    {
        $keypair = $this->unzer->fetchKeypair();
        $this->assertNotNull($keypair);
        $this->assertNotEmpty($keypair->getPublicKey());
        $this->assertNotEmpty($keypair->getPrivateKey());
        $this->assertNotEmpty($keypair->getAvailablePaymentTypes());
        $this->assertNotEmpty($keypair->getSecureLevel());
    }

    /**
     * Verify key pair config can be fetched with details.
     *
     * @test
     */
    public function keypairShouldBeFetchableWithDetails(): void
    {
        $keypair = $this->unzer->fetchKeypair(true);
        $this->assertNotNull($keypair);
        $this->assertNotEmpty($keypair->getPublicKey());
        $this->assertNotEmpty($keypair->getPrivateKey());
        $this->assertNotEmpty($keypair->getPaymentTypes());
        $this->assertNotEmpty($keypair->getSecureLevel());
    }
}
