<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify cancellation of authorizations.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\TransactionTypes;

use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\test\BaseIntegrationTest;

class CancelAfterAuthorizationTest extends BaseIntegrationTest
{
    /**
     * Verify that a full cancel on an authorization results in a cancelled payment.
     *
     * @test
     */
    public function fullCancelOnAuthorization(): void
    {
        $card = $this->unzer->createPaymentType($this->createCardObject());
        $authorization = $this->unzer->authorize(100.0000, 'EUR', $card, self::RETURN_URL, null, null, null, null, false);

        /** @var Authorization $fetchedAuthorization */
        $fetchedAuthorization = $this->unzer->fetchAuthorization($authorization->getPayment()->getId());
        $payment = $fetchedAuthorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertEquals('EUR', $payment->getCurrency());
        $this->assertTrue($payment->isPending());

        $cancellation = $fetchedAuthorization->cancel();
        $secPayment = $this->unzer->fetchPayment($payment->getId());
        $this->assertNotEmpty($cancellation);
        $this->assertAmounts($secPayment, 0.0, 0.0, 0.0, 0.0);
        $this->assertTrue($secPayment->isCanceled());

        $traceId = $cancellation->getTraceId();
        $this->assertNotEmpty($traceId);
        $this->assertSame($traceId, $cancellation->getPayment()->getTraceId());
    }

    /**
     * Verify part cancel on an authorization.
     *
     * @test
     */
    public function partCancelOnPayment(): void
    {
        $card = $this->unzer->createPaymentType($this->createCardObject());
        $authorization = $this->unzer->authorize(100.0000, 'EUR', $card, self::RETURN_URL, null, null, null, null, false);
        $payment = $this->unzer->fetchPayment($authorization->getPayment()->getId());

        $cancelArray = $payment->cancelAmount(10.0);

        $cancel = $cancelArray[0];
        $this->assertTransactionResourceHasBeenCreated($cancel);
        $this->assertEquals(10.0, $cancel->getAmount());
    }

    /**
     * Verify part cancel after authorization.
     *
     * @test
     */
    public function partCancelOnAuthorize(): void
    {
        $card = $this->unzer->createPaymentType($this->createCardObject());
        $authorization = $this->unzer->authorize(100.0000, 'EUR', $card, self::RETURN_URL, null, null, null, null, false);

        /** @var Authorization $fetchedAuthorization */
        $fetchedAuthorization = $this->unzer->fetchAuthorization($authorization->getPayment()->getId());

        $cancel = $fetchedAuthorization->cancel(10.0);
        $this->assertTransactionResourceHasBeenCreated($cancel);
        $this->assertEquals(10.0, $cancel->getAmount());

        $payment = $this->unzer->fetchPayment($fetchedAuthorization->getPayment()->getId());
        $this->assertAmounts($payment, 90.0, 0.0, 90.0, 0.0);
        $this->assertTrue($payment->isPending());
    }

    /**
     * Verify a cancel can be fetched.
     *
     * @test
     */
    public function anAuthorizationsFullReversalShallBeFetchable(): void
    {
        $card = $this->unzer->createPaymentType($this->createCardObject());
        $authorization = $this->unzer->authorize(100.0000, 'EUR', $card, self::RETURN_URL, null, null, null, null, false);
        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0, 100.0, 0);
        $this->assertTrue($payment->isPending());

        $cancel = $this->unzer->cancelAuthorization($authorization);
        $this->assertTransactionResourceHasBeenCreated($cancel);
        $this->assertEquals(100.0, $cancel->getAmount());
        $secondPayment = $cancel->getPayment();
        $this->assertAmounts($secondPayment, 0, 0, 0, 0);
        $this->assertTrue($secondPayment->isCanceled());


        $fetchedCancel = $this->unzer->fetchReversalByAuthorization($authorization, $cancel->getId());
        $this->assertTransactionResourceHasBeenCreated($fetchedCancel);
        $thirdPayment = $authorization->getPayment();
        $this->assertAmounts($thirdPayment, 0, 0, 0, 0);
        $this->assertTrue($thirdPayment->isCanceled());

        $fetchedCancelSecond = $this->unzer->fetchReversal($authorization->getPayment()->getId(), $cancel->getId());
        $this->assertTransactionResourceHasBeenCreated($fetchedCancelSecond);
        $this->assertEquals($fetchedCancel->expose(), $fetchedCancelSecond->expose());
        $fourthPayment = $fetchedCancelSecond->getPayment();
        $this->assertAmounts($fourthPayment, 0, 0, 0, 0);
        $this->assertTrue($fourthPayment->isCanceled());
    }

    /**
     * Verify cancels can be fetched.
     *
     * @test
     */
    public function anAuthorizationsReversalsShouldBeFetchable(): void
    {
        $card = $this->unzer->createPaymentType($this->createCardObject());
        $authorization = $this->unzer->authorize(100.0000, 'EUR', $card, self::RETURN_URL, null, null, null, null, false);
        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0, 100.0, 0);
        $this->assertTrue($payment->isPending());

        $firstCancel = $this->unzer->cancelAuthorization($authorization, 50.0);
        $this->assertNotNull($firstCancel);
        $this->assertNotNull($firstCancel->getId());
        $this->assertEquals(50.0, $firstCancel->getAmount());
        $secondPayment = $firstCancel->getPayment();
        $this->assertAmounts($secondPayment, 50.0, 0, 50.0, 0);
        $this->assertTrue($secondPayment->isPending());
        $this->assertCount(1, $authorization->getCancellations());

        $secondCancel = $this->unzer->cancelAuthorization($authorization, 20.0);
        $this->assertNotNull($secondCancel);
        $this->assertNotNull($secondCancel->getId());
        $this->assertEquals(20.0, $secondCancel->getAmount());
        $thirdPayment = $secondCancel->getPayment();
        $this->assertAmounts($thirdPayment, 30.0, 0, 30.0, 0);
        $this->assertTrue($thirdPayment->isPending());
        $this->assertCount(2, $authorization->getCancellations());

        $firstCancelFetched = $this->unzer->fetchReversalByAuthorization($authorization, $firstCancel->getId());
        $this->assertNotNull($firstCancelFetched);
        $this->assertEquals($firstCancel->expose(), $firstCancelFetched->expose());

        $secondCancelFetched = $this->unzer->fetchReversalByAuthorization($authorization, $secondCancel->getId());
        $this->assertNotNull($secondCancelFetched);
        $this->assertEquals($secondCancel->expose(), $secondCancelFetched->expose());
    }
}
