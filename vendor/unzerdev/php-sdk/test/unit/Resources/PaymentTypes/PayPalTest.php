<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of PayPal payment type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\test\BasePaymentTest;

class PayPalTest extends BasePaymentTest
{
    /**
     * Verify the bic can be set and read.
     *
     * @test
     */
    public function bicShouldBeRW(): void
    {
        $paypal = new Paypal();
        $this->assertNull($paypal->getEmail());
        $paypal->setEmail('test mail');
        $this->assertEquals('test mail', $paypal->getEmail());
    }
}
