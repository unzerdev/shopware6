<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and functionality of the payment method paypal.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\OpenbankingPis;
use UnzerSDK\test\BaseIntegrationTest;

class OpenBankingTest extends BaseIntegrationTest
{
    /**
     * Verify OpenBanking payment type can be created and fetched.
     *
     * @test
     *
     * @return BasePaymentType
     */
    public function openBankingShouldBeCreatableAndFetchable(): BasePaymentType
    {
        $openBanking = $this->unzer->createPaymentType(new OpenbankingPis('DE'));
        $this->assertInstanceOf(OpenbankingPis::class, $openBanking);
        $this->assertNotEmpty($openBanking->getId());

        $fetchedOpenBanking = $this->unzer->fetchPaymentType($openBanking->getId());
        $this->assertInstanceOf(OpenbankingPis::class, $fetchedOpenBanking);
        $this->assertNotSame($openBanking, $fetchedOpenBanking);
        $this->assertEquals($openBanking->expose(), $fetchedOpenBanking->expose());

        return $fetchedOpenBanking;
    }



    /**
     * Verify OpenBanking can charge.
     *
     * @test
     *
     * @depends openBankingShouldBeCreatableAndFetchable
     *
     * @param OpenbankingPis $openBanking
     */
    public function openBankingShouldBeChargeable(OpenbankingPis $openBanking): void
    {
        $charge = $this->unzer->performCharge(100.0, 'EUR', self::RETURN_URL);
        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
    }


}
