<?php

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use UnzerSDK\Resources\PaymentTypes\Clicktopay;
use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\test\Fixtures\JsonProvider;

class ClickToPayTest extends BasePaymentTest
{
    /**
     * Verify the resource data is set properly.
     *
     * @test
     */
    public function constructorShouldSetParameters(): void
    {
        $correlationId = 'corr12345';
        $brand = 'mastercard';
        $mcCxFlowId = '34f4a04b.5ab95e32-30f7-483f-846f-a08230a6d2ed.1618397078';
        $mcMerchantTransactionId = "0a4e0d3.34f4a04b.894125b16ddd1f1b3a58273d63a0894179ac3535";

        $clickToPay = new Clicktopay($correlationId, $mcCxFlowId, $mcMerchantTransactionId, $brand);


        $this->assertEquals($correlationId, $clickToPay->getMcCorrelationId());
        $this->assertEquals($brand, $clickToPay->getBrand());
        $this->assertEquals($mcCxFlowId, $clickToPay->getMcCxFlowId());
        $this->assertEquals($mcMerchantTransactionId, $clickToPay->getMcMerchantTransactionId());
    }

    /**
     * Test  ClickToPay json serialization.
     *
     * @test
     */
    public function jsonSerialization(): void
    {
        $clickToPayObject = new Clicktopay(
            "corr12345",
            "34f4a04b.5ab95e32-30f7-483f-846f-a08230a6d2ed.1618397078",
            "0a4e0d3.34f4a04b.894125b16ddd1f1b3a58273d63a0894179ac3535",
            "mastercard"
        );

        $expectedJson = JsonProvider::getJsonFromFile('clicktopay/createRequest.json');
        $this->assertJsonStringEqualsJsonString($expectedJson, $clickToPayObject->jsonSerialize());
    }

    /**
     * Test Click To Pay json response handling.
     *
     * @test
     */
    public function clickToPayAuthorizationShouldBeMappedCorrectly(): void
    {
        $clickToPay = new Clicktopay(null, null, null, null);

        $jsonResponse = JsonProvider::getJsonFromFile('clicktopay/fetchResponse.json');

        $jsonObject = json_decode($jsonResponse, false, 512, JSON_THROW_ON_ERROR);
        $clickToPay->handleResponse($jsonObject);

        $this->assertEquals('s-ctp-q0nucec6itwe', $clickToPay->getId());
    }
}
