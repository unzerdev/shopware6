<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify keypair functionalities.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\Resources;

use RuntimeException;
use UnzerSDK\Resources\Keypair;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\Unzer;

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
    public function keypairShouldReturnExpectedValues(): Keypair
    {
        $keypair = $this->unzer->fetchKeypair();
        $this->assertNotNull($keypair);
        $this->assertNotEmpty($keypair->getPublicKey());
        $this->assertNotEmpty($keypair->getAvailablePaymentTypes());
        $this->assertNotEmpty($keypair->getSecureLevel());
        return $keypair;
    }

    /**
     * Verify keypair can be fetched using the public key extracted from a previous fetch.
     *
     * @test
     *
     * @depends keypairShouldReturnExpectedValues
     */
    public function keypairCanBeFetchedUsingPublicKey(Keypair $keypair): void
    {
        $publicKey = $keypair->getPublicKey();
        $fetchedKeypair = (new Unzer($publicKey))->fetchKeypair();
        $this->assertNotNull($fetchedKeypair);
        $this->assertEquals($publicKey, $fetchedKeypair->getPublicKey());
        $this->assertNotEmpty($fetchedKeypair->getSecureLevel());
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

    /**
     * Verify expose() reflects the fetched keypair's properties and excludes privateKey and detailed.
     *
     * @test
     */
    public function exposeReflectsKeypairPropertiesAfterFetch(): void
    {
        $keypair = $this->unzer->fetchKeypair(true);
        $exposed = $keypair->expose();

        // Main scalar properties must match keypair getters
        $this->assertEquals($keypair->getPublicKey(), $exposed['publicKey']);
        $this->assertEquals($keypair->getSecureLevel(), $exposed['secureLevel']);

        // Nested payment type objects must be present and match
        $this->assertNotEmpty($exposed['paymentTypes']);
        $this->assertEquals($keypair->getPaymentTypes(), $exposed['paymentTypes']);

        // privateKey (private) and detailed (private) must never be exposed
        $this->assertArrayNotHasKey('privateKey', $exposed);
        $this->assertArrayNotHasKey('detailed', $exposed);
    }
}
