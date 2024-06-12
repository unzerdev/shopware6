<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of Applepay payment type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use UnzerSDK\Resources\EmbeddedResources\ApplePayHeader;
use UnzerSDK\Resources\PaymentTypes\Applepay;
use UnzerSDK\test\BasePaymentTest;

class ApplePayTest extends BasePaymentTest
{
    /**
     * Verify the resource data is set properly.
     *
     * @test
     */
    public function constructorShouldSetParameters(): void
    {
        $version = 'EC_v1';
        $data = 'some-Data';
        $signature = 'mySignature';
        $applepay       = new Applepay($version, $data, $signature, $this->getTestApplePayHeader());

        $this->assertEquals($version, $applepay->getVersion());
        $this->assertEquals($data, $applepay->getData());
        $this->assertEquals($signature, $applepay->getSignature());
        $this->assertInstanceOf(ApplePayHeader::class, $applepay->getHeader());
    }

    /**
     * Test Apple Pay json serialization.
     *
     * @test
     */
    public function jsonSerializationExposesOnlyRequestParameter(): void
    {
        $applepay = $this->getTestApplepay();

        $expectedJson = '{ "data": "data", "header": { "ephemeralPublicKey": "ephemeralPublicKey", "publicKeyHash": ' .
            '"publicKeyHash", "transactionId": "transactionId" }, "signature": "sig", "version": "EC_v1" }';

        $this->assertJsonStringEqualsJsonString($expectedJson, $applepay->jsonSerialize());
    }

    /**
     * Test Apple Pay json response handling.
     *
     * @test
     */
    public function responseShouldBeMappedCorrectly(): void
    {
        $applepay = new Applepay(null, null, null, null);

        $jsonResponse = '{
            "id": "s-apl-faucbirhd6yy",
            "method": "apple-pay",
            "recurring": false,
            "geoLocation": {
                "clientIp": "115.77.189.143",
                "countryCode": ""
            },
            "applicationPrimaryAccountNumber": "370295******922",
            "applicationExpirationDate": "07/2020",
            "currencyCode": "EUR",
            "transactionAmount": "1.5000"
        }';

        $applepay->handleResponse(json_decode($jsonResponse));

        $this->assertEquals('s-apl-faucbirhd6yy', $applepay->getId());
        $this->assertEquals('apple-pay', $applepay->getMethod());
        $this->assertEquals('370295******922', $applepay->getApplicationPrimaryAccountNumber());
        $this->assertEquals('07/2020', $applepay->getApplicationExpirationDate());
        $this->assertEquals('EUR', $applepay->getCurrencyCode());
        $this->assertSame(1.5000, $applepay->getTransactionAmount());
        $this->assertNotNull($applepay->getGeoLocation());
    }

    /**
     * Test Apple Pay json response handling.
     *
     * @test
     */
    public function applepayAuthorizationShouldBeMappedCorrectly(): void
    {
        $applepay = new Applepay(null, null, null, null);

        $jsonResponse = '{
          "version": "EC_v1",
          "data": "data",
          "signature": "signature",
          "header": {
            "ephemeralPublicKey": "ephemeralPublicKey",
            "publicKeyHash": "publicKeyHash",
            "transactionId": "transactionId"
          }
        }';

        $applepay->handleResponse(json_decode($jsonResponse));

        $this->assertEquals('EC_v1', $applepay->getVersion());
        $this->assertEquals('data', $applepay->getData());
        $this->assertEquals('signature', $applepay->getSignature());
        $applePayHeader = $applepay->getHeader();
        $this->assertNotNull($applePayHeader);
        $this->assertEquals('ephemeralPublicKey', $applePayHeader->getEphemeralPublicKey());
        $this->assertEquals('publicKeyHash', $applePayHeader->getPublicKeyHash());
        $this->assertEquals('transactionId', $applePayHeader->getTransactionId());
    }

    /**
     * @return ApplePayHeader
     */
    private function getTestApplePayHeader(): ApplePayHeader
    {
        return new ApplePayHeader('ephemeralPublicKey', 'publicKeyHash', 'transactionId');
    }

    /**
     * @return Applepay
     */
    private function getTestApplepay(): Applepay
    {
        return new Applepay('EC_v1', 'data', 'sig', $this->getTestApplePayHeader());
    }
}
