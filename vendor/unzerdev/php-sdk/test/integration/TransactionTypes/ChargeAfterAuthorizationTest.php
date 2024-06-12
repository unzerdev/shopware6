<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify charge after authorization.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\TransactionTypes;

use UnzerSDK\test\BaseIntegrationTest;

class ChargeAfterAuthorizationTest extends BaseIntegrationTest
{
    /**
     * Validate full charge after authorization.
     *
     * @test
     */
    public function authorizationShouldBeFullyChargeable(): void
    {
        $authorization = $this->createCardAuthorization();
        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 100, 0, 100, 0);
        $this->assertTrue($payment->isPending());

        $charge = $authorization->charge();
        $this->unzer->fetchPayment($payment);
        $this->assertNotNull($charge);
        $this->assertNotNull($charge->getId());
        $this->assertAmounts($payment, 0, 100, 100, 0);
        $this->assertTrue($payment->isCompleted());
    }

    /**
     * Validate full charge after authorization.
     *
     * @test
     */
    public function authorizationShouldBeFullyChargeableViaUnzerObject(): void
    {
        $authorization = $this->createCardAuthorization();
        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 100, 0, 100, 0);
        $this->assertTrue($payment->isPending());

        $charge = $this->unzer->chargeAuthorization($payment->getId());
        $this->unzer->fetchPayment($payment);
        $this->assertNotNull($charge);
        $this->assertNotNull($charge->getId());
        $this->assertAmounts($payment, 0, 100, 100, 0);
        $this->assertTrue($payment->isCompleted());
    }

    /**
     * Verify authorization is partly chargeable.
     *
     * @test
     */
    public function authorizationShouldBePartlyChargeable(): void
    {
        $authorization = $this->createCardAuthorization();
        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 100, 0, 100, 0);
        $this->assertTrue($payment->isPending());

        $charge = $this->unzer->chargeAuthorization($payment->getId(), 10);
        $this->unzer->fetchPayment($payment);
        $this->assertNotNull($charge);
        $this->assertNotNull($charge->getId());
        $this->assertAmounts($payment, 90, 10, 100, 0);
        $this->assertTrue($payment->isPartlyPaid());
    }
}
