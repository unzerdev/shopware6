<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;
use UnzerSDK\test\BasePaymentTest;

class SepaDirectDebitTest extends BasePaymentTest
{
    /**
     * Verify constructor sets iban.
     *
     * @test
     */
    public function ibanShouldBeSetByConstructor(): void
    {
        $sdd = new SepaDirectDebit(null);
        $this->assertNull($sdd->getIban());
    }

    /**
     * Verify setter and getter work.
     *
     * @test
     */
    public function getterAndSetterWorkAsExpected(): void
    {
        $sdd = new SepaDirectDebit('DE89370400440532013000');
        $this->assertEquals('DE89370400440532013000', $sdd->getIban());

        $sdd->setIban('DE89370400440532013012');
        $this->assertEquals('DE89370400440532013012', $sdd->getIban());

        $this->assertNull($sdd->getBic());
        $sdd->setBic('RABONL2U');
        $this->assertEquals('RABONL2U', $sdd->getBic());

        $this->assertNull($sdd->getHolder());
        $sdd->setHolder('Max Mustermann');
        $this->assertEquals('Max Mustermann', $sdd->getHolder());
    }
}
