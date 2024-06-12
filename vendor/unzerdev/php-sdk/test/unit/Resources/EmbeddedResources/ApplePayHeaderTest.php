<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the embedded Applepay header resource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\EmbeddedResources;

use UnzerSDK\Resources\EmbeddedResources\ApplePayHeader;
use PHPUnit\Framework\TestCase;

class ApplePayHeaderTest extends TestCase
{
    /**
     * Verify the resource data is set properly.
     *
     * @test
     */
    public function constructorShouldSetParameters(): void
    {
        $applepayHeader = new ApplePayHeader('ephemeralPublicKey', 'publicKeyHash', 'transactionId');

        $this->assertEquals('ephemeralPublicKey', $applepayHeader->getEphemeralPublicKey());
        $this->assertEquals('publicKeyHash', $applepayHeader->getPublicKeyHash());
        $this->assertEquals('transactionId', $applepayHeader->getTransactionId());
    }
}
