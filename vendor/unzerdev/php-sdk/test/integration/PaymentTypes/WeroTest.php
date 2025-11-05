<?php

/** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpDocMissingThrowsInspection */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\WeroAmountPaymentTypes;
use UnzerSDK\Constants\WeroCaptureTriggers;
use UnzerSDK\Resources\EmbeddedResources\WeroEventDependentPayment;
use UnzerSDK\Resources\EmbeddedResources\WeroTransactionData;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Wero;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;

class WeroTest extends BaseIntegrationTest
{
    /**
     * @test
     *
     * @return BasePaymentType
     */
    public function weroShouldBeCreatableAndFetchable(): BasePaymentType
    {
        $wero = $this->unzer->createPaymentType(new Wero());
        $this->assertInstanceOf(Wero::class, $wero);
        $this->assertNotEmpty($wero->getId());

        $fetched = $this->unzer->fetchPaymentType($wero->getId());
        $this->assertInstanceOf(Wero::class, $fetched);
        $this->assertNotSame($wero, $fetched);
        $this->assertEquals($wero->expose(), $fetched->expose());

        return $fetched;
    }

    /**
     * Verify Wero can authorize.
     *
     * @test
     *
     * @depends weroShouldBeCreatableAndFetchable
     */
    public function weroShouldBeAuthorizable(Wero $wero): void
    {
        $authorization = new Authorization(100.0, 'EUR', self::RETURN_URL);

        // Add Wero additional transaction data
        $weroData = (new WeroTransactionData())
            ->setEventDependentPayment(
                (new WeroEventDependentPayment())
                    ->setCaptureTrigger(WeroCaptureTriggers::SERVICEFULFILMENT)
                    ->setAmountPaymentType(WeroAmountPaymentTypes::PAY)
                    ->setMaxAuthToCaptureTime(300)
                    ->setMultiCapturesAllowed(false)
            );
        $authorization->setWeroTransactionData($weroData);

        $authorization = $this->unzer->performAuthorization($authorization, $wero);
        $this->assertNotNull($authorization);
        $this->assertNotEmpty($authorization->getId());
        $this->assertNotEmpty($authorization->getRedirectUrl());

        $payment = $authorization->getPayment();
        $this->assertNotNull($payment);
        $this->assertTrue($payment->isPending());
    }

    /**
     * Verify Wero can charge.
     *
     * @test
     *
     * @depends weroShouldBeCreatableAndFetchable
     */
    public function weroShouldBeChargeable(Wero $wero): void
    {
        $charge = new Charge(100.0, 'EUR', self::RETURN_URL);

        // Add Wero additional transaction data
        $weroData = (new WeroTransactionData())
            ->setEventDependentPayment(
                (new WeroEventDependentPayment())
                    ->setCaptureTrigger(WeroCaptureTriggers::SERVICEFULFILMENT)
                    ->setAmountPaymentType(WeroAmountPaymentTypes::PAY)
                    ->setMaxAuthToCaptureTime(300)
                    ->setMultiCapturesAllowed(false)
            );
        $charge->setWeroTransactionData($weroData);

        $charge = $this->unzer->performCharge($charge, $wero);
        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
        $this->assertNotEmpty($charge->getRedirectUrl());

        $fetched = $this->unzer->fetchChargeById($charge->getPayment()->getId(), $charge->getId());

        $this->assertEquals(($charge->getPaymentId()), $fetched->getPaymentId());
        $this->assertEquals(($charge->getAmount()), $fetched->getAmount());
        $this->assertEquals(($charge->getId()), $fetched->getId());
    }
}
