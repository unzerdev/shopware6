<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of Bancontact payment type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use UnzerSDK\Resources\PaymentTypes\Bancontact;
use UnzerSDK\test\BasePaymentTest;

class BancontactTest extends BasePaymentTest
{
    /**
     * Verify getters and setters work as expected.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkAsExpected(): void
    {
        $bancontact = new Bancontact();
        $this->assertNull($bancontact->getHolder());
        $bancontact->setHolder('Max Mustermann');
        $this->assertEquals('Max Mustermann', $bancontact->getHolder());
        $bancontact->setHolder(null);
        $this->assertNull($bancontact->getHolder());
    }
}
