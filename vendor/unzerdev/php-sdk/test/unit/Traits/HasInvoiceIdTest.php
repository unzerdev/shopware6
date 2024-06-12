<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the HasInvoiceId trait.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Traits;

use UnzerSDK\test\BasePaymentTest;

class HasInvoiceIdTest extends BasePaymentTest
{
    /**
     * Verify getters and setters.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkAsExpected(): void
    {
        $dummy = new TraitDummyHasInvoiceId();
        $this->assertNull($dummy->getInvoiceId());

        $dummy->setInvoiceId('myInvoiceId');
        $this->assertEquals('myInvoiceId', $dummy->getInvoiceId());
    }
}
