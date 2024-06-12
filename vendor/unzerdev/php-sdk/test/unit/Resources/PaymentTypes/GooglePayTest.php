<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use UnzerSDK\Resources\EmbeddedResources\GooglePay\IntermediateSigningKey;
use UnzerSDK\Resources\EmbeddedResources\GooglePay\SignedKey;
use UnzerSDK\Resources\EmbeddedResources\GooglePay\SignedMessage;
use UnzerSDK\Resources\PaymentTypes\Googlepay;
use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\test\Fixtures\JsonProvider;

/**
 * This class defines unit tests to verify functionality of Googlepay payment type.
 */
class GooglePayTest extends BasePaymentTest
{
    /**
     * Verify the resource data is set properly.
     *
     * @test
     */
    public function constructorShouldSetParameters(): void
    {
        $protocolVersion = 'EC_v2';
        $signature = 'mySignature';
        $intermediaSigningKey = new IntermediateSigningKey();
        $signedMessage = new SignedMessage('tag', 'eph', 'encryptedMessage');
        $googlepay = new Googlepay($protocolVersion, $signature, $intermediaSigningKey, $signedMessage);

        $this->assertEquals($protocolVersion, $googlepay->getProtocolVersion());
        $this->assertEquals($signature, $googlepay->getSignature());
        $this->assertEquals($intermediaSigningKey, $googlepay->getIntermediateSigningKey());
        $this->assertEquals($signedMessage, $googlepay->getSignedMessage());
    }

    /**
     * Test Google Pay json serialization.
     *
     * @test
     */
    public function jsonSerializationExposesOnlyRequestParameter(): void
    {
        $googlepay = $this->getTestGooglepay();

        $expectedJson = JsonProvider::getJsonFromFile('googlePay/createRequest.json');
        $this->assertJsonStringEqualsJsonString($expectedJson, $googlepay->jsonSerialize());
    }

    /**
     * @return Googlepay
     */
    private function getTestGooglepay(): Googlepay
    {
        $intermediateSigningKey = (new IntermediateSigningKey())
            ->setSignatures(['signature1'])
            ->setSignedKey(new SignedKey('1542394027316', 'key-value-xyz\\u003d\\u003d"}'));
        return new Googlepay(
            'ECv2',
            'signature-xyz=',
            $intermediateSigningKey,
            new SignedMessage(
                '001 Cryptogram 3ds',
                'ephemeralPublicKey-xyz\\u003d"',
                'encryptedMessage-xyz"'
            )
        );
    }

    /**
     * Test GooglePay json response handling.
     *
     * @test
     */
    public function googlepayAuthorizationShouldBeMappedCorrectly(): void
    {
        $googlepay = new Googlepay(null, null, null, null);

        $jsonResponse = JsonProvider::getJsonFromFile('googlePay/fetchResponse.json');

        $jsonObject = json_decode($jsonResponse, false, 512, JSON_THROW_ON_ERROR);
        $googlepay->handleResponse($jsonObject);

        $this->assertEquals('s-gop-q0nucec6itwe', $googlepay->getId());
        $this->assertEquals('10/2025', $googlepay->getExpiryDate());
        $this->assertEquals('518834******0003', $googlepay->getNumber());
    }
}
