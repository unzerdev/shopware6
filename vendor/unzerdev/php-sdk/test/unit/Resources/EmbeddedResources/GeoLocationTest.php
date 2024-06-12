<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the embedded GeoLocation resource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\EmbeddedResources;

use UnzerSDK\Resources\EmbeddedResources\GeoLocation;
use UnzerSDK\test\BasePaymentTest;

class GeoLocationTest extends BasePaymentTest
{
    /**
     * Verify setter and getter functionalities.
     *
     * @test
     */
    public function settersAndGettersShouldWork(): void
    {
        $geoLocation = new GeoLocation();
        $this->assertNull($geoLocation->getCountryCode());
        $this->assertNull($geoLocation->getClientIp());

        $response = ['countryCode' => 'myCountryCode', 'clientIp' => '127.0.0.1'];
        $geoLocation->handleResponse((object) $response);

        $this->assertEquals('myCountryCode', $geoLocation->getCountryCode());
        $this->assertEquals('127.0.0.1', $geoLocation->getClientIp());

        // Secondary setter works as well
        $response = ['countryIsoA2' => 'differentCountryCode'];
        $geoLocation->handleResponse((object) $response);

        $this->assertEquals('differentCountryCode', $geoLocation->getCountryCode());
    }
}
