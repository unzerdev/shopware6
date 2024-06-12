<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify cancellation in general.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\TransactionTypes;

use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\test\BaseIntegrationTest;

class CancelTest extends BaseIntegrationTest
{
    /**
     * Verify reversal is fetchable.
     *
     * @test
     */
    public function reversalShouldBeFetchableViaUnzerObject(): void
    {
        $authorization = $this->createCardAuthorization();
        $cancel = $authorization->cancel();
        $fetchedCancel = $this->unzer->fetchReversal($authorization->getPayment()->getId(), $cancel->getId());
        $this->assertTransactionResourceHasBeenCreated($fetchedCancel);
        $this->assertEquals($cancel->expose(), $fetchedCancel->expose());
    }

    /**
     * Verify reversal is fetchable.
     *
     * @test
     */
    public function reversalShouldBeFetchableViaPaymentObject(): void
    {
        $authorization = $this->createCardAuthorization();
        $cancel = $authorization->cancel();
        $fetchedCancel = $cancel->getPayment()->getAuthorization()->getCancellation($cancel->getId());
        $this->assertTransactionResourceHasBeenCreated($fetchedCancel);
        $this->assertEquals($cancel->expose(), $fetchedCancel->expose());
    }

    /**
     * Verify refund is fetchable.
     *
     * @test
     */
    public function refundShouldBeFetchableViaUnzerObject(): void
    {
        $this->useLegacyKey();
        $charge = $this->createCharge();
        $cancel = $charge->cancel();
        $fetchedCancel = $this->unzer->fetchRefundById($charge->getPayment()->getId(), $charge->getId(), $cancel->getId());
        $this->assertTransactionResourceHasBeenCreated($fetchedCancel);
        $this->assertEquals($cancel->expose(), $fetchedCancel->expose());
    }

    /**
     * Verify refund is fetchable.
     *
     * @test
     */
    public function refundShouldBeFetchableViaPaymentObject(): void
    {
        $this->useLegacyKey();
        $charge = $this->createCharge();
        $cancel = new Cancellation();
        $this->getUnzerObject()->cancelChargedPayment($charge->getPayment(), $cancel);
        $fetchedCancel = $cancel->getPayment()->getCharge($charge->getId())->getCancellation($cancel->getId());
        $this->assertTransactionResourceHasBeenCreated($fetchedCancel);
        $this->assertEquals($cancel->expose(), $fetchedCancel->expose());
    }

    /**
     * Verify reversal is fetchable via payment object.
     *
     * @test
     */
    public function authorizationCancellationsShouldBeFetchableViaPaymentObject(): void
    {
        $authorization = $this->createCardAuthorization();
        $reversal = $authorization->cancel();
        $fetchedPayment = $this->unzer->fetchPayment($authorization->getPayment()->getId());

        $cancellation = $fetchedPayment->getAuthorization()->getCancellation($reversal->getId());
        $this->assertTransactionResourceHasBeenCreated($cancellation);
        $this->assertEquals($cancellation->expose(), $reversal->expose());
    }

    /**
     * Verify refund is fetchable via payment object.
     *
     * @test
     */
    public function chargeCancellationsShouldBeFetchableViaPaymentObject(): void
    {
        $this->useLegacyKey();
        $charge = $this->createCharge();
        $refund = $charge->cancel();
        $fetchedPayment = $this->unzer->fetchPayment($charge->getPayment()->getId());

        $cancellation = $fetchedPayment->getCharge($charge->getId())->getCancellation($refund->getId());
        $this->assertTransactionResourceHasBeenCreated($cancellation);
        $this->assertEquals($cancellation->expose(), $refund->expose());
    }

    /**
     * Verify transaction status.
     *
     * @test
     */
    public function cancelStatusIsSetCorrectly(): void
    {
        $this->useLegacyKey();
        $charge = $this->createCharge();
        $reversal = $charge->cancel();
        $this->assertSuccess($reversal);
    }
}
