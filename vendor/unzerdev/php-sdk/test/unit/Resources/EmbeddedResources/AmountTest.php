<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the embedded Amount resource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\EmbeddedResources;

use UnzerSDK\Resources\EmbeddedResources\Amount;
use UnzerSDK\test\BasePaymentTest;

class AmountTest extends BasePaymentTest
{
    /**
     * Verify setter and getter functionalities.
     *
     * @test
     *
     */
    public function settersAndGettersShouldWork(): void
    {
        $amount = new Amount();
        $this->assertNull($amount->getCurrency());
        $this->assertEquals(0.0, $amount->getTotal());
        $this->assertEquals(0.0, $amount->getCanceled());
        $this->assertEquals(0.0, $amount->getCharged());
        $this->assertEquals(0.0, $amount->getRemaining());

        $resp = ['total' => 1.1, 'canceled' => 2.2, 'charged' => 3.3, 'remaining' => 4.4, 'currency' => 'MyCurrency'];
        $amount->handleResponse((object)$resp);

        $this->assertEquals('MyCurrency', $amount->getCurrency());
        $this->assertEquals(1.1, $amount->getTotal());
        $this->assertEquals(2.2, $amount->getCanceled());
        $this->assertEquals(3.3, $amount->getCharged());
        $this->assertEquals(4.4, $amount->getRemaining());
    }
}
