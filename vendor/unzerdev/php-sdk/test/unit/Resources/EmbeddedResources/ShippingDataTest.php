<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the embedded shipping resource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\EmbeddedResources;

use UnzerSDK\Resources\EmbeddedResources\ShippingData;
use UnzerSDK\test\BasePaymentTest;

class ShippingDataTest extends BasePaymentTest
{
    /**
     * Verify setter and getter functionalities.
     *
     * @test
     *
     */
    public function settersAndGettersShouldWork(): void
    {
        $shipping = new ShippingData();
        $this->assertNull($shipping->getDeliveryService());
        $this->assertNull($shipping->getDeliveryTrackingId());
        $this->assertNull($shipping->getReturnTrackingId());

        $resp = [
            "deliveryTrackingId" => "deliveryTrackingId",
            "deliveryService" => "deliveryService",
            "returnTrackingId" => "returnTrackingId",
        ];
        $shipping->handleResponse((object)$resp);

        $this->assertEquals('deliveryTrackingId', $shipping->getDeliveryTrackingId());
        $this->assertEquals('deliveryService', $shipping->getDeliveryService());
        $this->assertEquals('returnTrackingId', $shipping->getReturnTrackingId());
    }
}
