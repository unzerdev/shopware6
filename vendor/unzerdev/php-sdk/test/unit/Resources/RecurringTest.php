<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the Recurring resource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources;

use UnzerSDK\Resources\Recurring;
use UnzerSDK\test\BasePaymentTest;

class RecurringTest extends BasePaymentTest
{
    /**
     * Verify getters and setters.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkAsExpected(): void
    {
        $recurring = new Recurring('payment type id', $this::RETURN_URL);
        $this->assertEquals('payment type id', $recurring->getPaymentTypeId());
        $this->assertEquals($this::RETURN_URL, $recurring->getReturnUrl());

        $recurring->handleResponse((object)['redirectUrl' => 'redirect url']);
        $this->assertEquals('redirect url', $recurring->getRedirectUrl());
        $recurring->handleResponse((object)['redirectUrl' => 'different redirect url']);
        $this->assertEquals('different redirect url', $recurring->getRedirectUrl());

        $recurring->setPaymentTypeId('another type id');
        $this->assertEquals('another type id', $recurring->getPaymentTypeId());

        $recurring->setReturnUrl('another Return url');
        $this->assertEquals('another Return url', $recurring->getReturnUrl());
    }
}
