<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of Ideal payment type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use UnzerSDK\Resources\PaymentTypes\Ideal;
use UnzerSDK\test\BasePaymentTest;

class IdealTest extends BasePaymentTest
{
    /**
     * Verify the bic can be set and read.
     *
     * @test
     */
    public function bicShouldBeRW(): void
    {
        $ideal = new Ideal();
        $this->assertNull($ideal->getBic());
        $ideal->setBic('RABONL2U');
        $this->assertEquals('RABONL2U', $ideal->getBic());
    }
}
