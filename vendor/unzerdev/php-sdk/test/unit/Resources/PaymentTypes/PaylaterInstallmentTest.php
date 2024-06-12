<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of Paylater Installment payment type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use UnzerSDK\Resources\PaymentTypes\PaylaterInstallment;
use UnzerSDK\test\BasePaymentTest;

class PaylaterInstallmentTest extends BasePaymentTest
{
    /**
     * Verify getters and setters work as expected.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkAsExpected(): void
    {
        $pit = new PaylaterInstallment();
        $this->assertNull($pit->getInquiryId());
        $this->assertNull($pit->getNumberOfRates());
        $this->assertNull($pit->getIban());
        $this->assertNull($pit->getCountry());
        $this->assertNull($pit->getHolder());

        $pit->setInquiryId('inquiryId');
        $pit->setNumberOfRates(7);
        $pit->setIban('DE89370400440532013000');
        $pit->setCountry('DE');
        $pit->setHolder('Max Mustermann');

        $this->assertEquals('inquiryId', $pit->getInquiryId());
        $this->assertEquals(7, $pit->getNumberOfRates());
        $this->assertEquals('DE89370400440532013000', $pit->getIban());
        $this->assertEquals('DE', $pit->getCountry());
        $this->assertEquals('Max Mustermann', $pit->getHolder());

        $pit->setInquiryId(null);
        $pit->setNumberOfRates(null);
        $pit->setIban(null);
        $pit->setCountry(null);
        $pit->setHolder(null);

        $this->assertNull($pit->getInquiryId());
        $this->assertNull($pit->getNumberOfRates());
        $this->assertNull($pit->getIban());
        $this->assertNull($pit->getCountry());
        $this->assertNull($pit->getHolder());
    }
}
