<?php

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use UnzerSDK\Resources\PaymentTypes\PaylaterDirectDebit;
use UnzerSDK\test\BasePaymentTest;

class PaylaterDirectDebitTest extends BasePaymentTest
{
    /**
     * Verify constructor sets iban properties.
     *
     * @test
     */
    public function constructorShouldSetProperties(): void
    {
        $pdd = new PaylaterDirectDebit('iban', 'holder');
        $this->assertEquals('iban', $pdd->getIban());
        $this->assertEquals('holder', $pdd->getHolder());
    }

    /**
     * Verify setter and getter work.
     *
     * @test
     */
    public function getterAndSetterWorkAsExpected(): void
    {
        $pdd = new PaylaterDirectDebit();

        $this->assertNull($pdd->getIban());
        $pdd->setIban('DE89370400440532013000');
        $this->assertEquals('DE89370400440532013000', $pdd->getIban());

        $this->assertNull($pdd->getHolder());
        $pdd->setHolder('Max Mustermann');
        $this->assertEquals('Max Mustermann', $pdd->getHolder());
    }
}
