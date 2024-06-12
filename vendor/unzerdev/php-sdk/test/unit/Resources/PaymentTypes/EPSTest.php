<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of EPS payment type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use UnzerSDK\Resources\PaymentTypes\EPS;
use UnzerSDK\test\BasePaymentTest;

class EPSTest extends BasePaymentTest
{
    /**
     * Verify getters and setters work as expected.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkAsExpected(): void
    {
        $eps = new EPS();
        $this->assertNull($eps->getBic());
        $eps->setBic('12345676XXX');
        $this->assertEquals('12345676XXX', $eps->getBic());
    }
}
