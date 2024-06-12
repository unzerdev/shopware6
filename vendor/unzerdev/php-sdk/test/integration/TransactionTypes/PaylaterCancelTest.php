<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify cancellation of paylater invoice type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\TransactionTypes;

use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;

/** Testing cancellations with payment type paylater-invoice. Default authorization amount is 99.99 €. */
class PaylaterCancelTest extends BaseIntegrationTest
{
    /**
     * @test
     */
    public function reversalIsPossibleViaUnzerFacade(): void
    {
        $authorization = $this->createPaylaterInvoiceAuthorization();
        $payment = $authorization->getPayment();
        $cancellation = (new Cancellation())->setInvoiceId('i' . self::generateRandomId());
        $cancel = $this->unzer->cancelAuthorizedPayment($payment, $cancellation);

        $this->assertTrue($cancel->isSuccess());
        $this->assertNull($cancel->getParentResource()->getId());
        $this->assertCount(1, $payment->getCancellations());
        $this->assertCount(1, $payment->getReversals());
        $this->assertCount(0, $authorization->getCancellations());
    }

    /**
     * @test
     */
    public function reversalIsPossibleWOCancellationObject(): void
    {
        $authorization = $this->createPaylaterInvoiceAuthorization();
        $payment = $authorization->getPayment();
        $cancel = $this->unzer->cancelAuthorizedPayment($payment);

        $this->assertTrue($cancel->isSuccess());
        $this->assertNull($cancel->getParentResource()->getId());
        $this->assertCount(1, $payment->getCancellations());
        $this->assertCount(1, $payment->getReversals());
        $this->assertCount(0, $authorization->getCancellations());
    }

    /**
     * @test
     */
    public function reversalIsFetchableViaUnzerFacade(): void
    {
        $authorization = $this->createPaylaterInvoiceAuthorization();
        $payment = $authorization->getPayment();
        $cancel = $this->unzer->cancelAuthorizedPayment($payment, new Cancellation());
        $fetchedCancel = $this->unzer->fetchPaymentReversal($payment, $cancel->getId());

        $this->assertNull($fetchedCancel->getParentResource()->getId());
        $this->assertEquals($cancel->getShortId(), $fetchedCancel->getShortId());
        $this->assertEquals($payment, $fetchedCancel->getPayment());
        $this->assertCount(1, $payment->getReversals());
        $this->assertCount(0, $payment->getRefunds());
    }

    /**
     * @test
     */
    public function verifyPartReversalIsPossible(): void
    {
        $authorization = $this->createPaylaterInvoiceAuthorization();
        $reversalAmount = 33.33;
        $this->assertAmounts($authorization->getPayment(), 99.99, 0, 99.99, 0);

        $this->unzer->cancelAuthorizedPayment($authorization->getPayment(), new Cancellation($reversalAmount));
        $this->assertAmounts($authorization->getPayment(), 66.66, 0, 66.66, 0);
    }

    /**
     * @test
     */
    public function fullRefundWorksViaUnzerFacadeAsExpected(): void
    {
        $authorization = $this->createPaylaterInvoiceAuthorization();
        $payment = $authorization->getPayment();
        $charge = $this->unzer->performChargeOnPayment($payment, new Charge());
        $cancellation = (new Cancellation())->setInvoiceId('i' . self::generateRandomId());
        $cancel = $this->unzer->cancelChargedPayment($payment, $cancellation);

        $this->assertInstanceOf(Charge::class, $cancel->getParentResource());
        $this->assertNull($cancel->getParentResource()->getId());

        $this->assertTrue($cancel->isSuccess());
        $this->assertCount(1, $payment->getCancellations());
        $this->assertCount(1, $payment->getRefunds());
        $this->assertCount(0, $payment->getReversals());
        $this->assertCount(0, $charge->getCancellations());
    }

    /**
     * @test
     */
    public function fullRefundIsPossibleWOCancellationObject(): void
    {
        $authorization = $this->createPaylaterInvoiceAuthorization();
        $payment = $authorization->getPayment();
        $charge = $this->unzer->performChargeOnPayment($payment, new Charge());
        $cancel = $this->unzer->cancelChargedPayment($payment);

        $this->assertInstanceOf(Charge::class, $cancel->getParentResource());
        $this->assertNull($cancel->getParentResource()->getId());

        $this->assertTrue($cancel->isSuccess());
        $this->assertCount(1, $payment->getCancellations());
        $this->assertCount(1, $payment->getRefunds());
        $this->assertCount(0, $payment->getReversals());
        $this->assertCount(0, $charge->getCancellations());
    }

    /**
     * @test
     */
    public function partRefundIsPossibleViaUnzerFacade(): void
    {
        $authorization = $this->createPaylaterInvoiceAuthorization();
        $payment = $authorization->getPayment();
        $this->unzer->performChargeOnPayment($payment, new Charge());

        $cancel1 = $this->unzer->cancelChargedPayment($payment, new Cancellation(22.22));
        $cancel2 = $this->unzer->cancelChargedPayment($payment, new Cancellation(33.33));
        $cancel3 = $this->unzer->cancelChargedPayment($payment, new Cancellation(44.44));

        $this->assertTrue($cancel1->isSuccess());
        $this->assertEquals(22.22, $cancel1->getAmount());

        $this->assertTrue($cancel2->isSuccess());
        $this->assertEquals(33.33, $cancel2->getAmount());

        $this->assertTrue($cancel3->isSuccess());
        $this->assertEquals(44.44, $cancel3->getAmount());

        $this->assertCount(3, $payment->getCancellations());
        $refunds = $payment->getRefunds();
        $this->assertCount(3, $refunds);
        $this->assertArrayHasKey('s-cnl-1', $refunds);
        $this->assertArrayHasKey('s-cnl-2', $refunds);
        $this->assertArrayHasKey('s-cnl-3', $refunds);
    }

    /**
     * @test
     */
    public function reversalOnChargedPaymentThrowsException(): void
    {
        $authorization = $this->createPaylaterInvoiceAuthorization();
        $payment = $authorization->getPayment();
        $charge1 = $this->unzer->performChargeOnPayment($payment, new Charge(11.11));
        $this->expectException(UnzerApiException::class);

        $cancel = $this->unzer->cancelAuthorizedPayment($payment, new Cancellation());
        $this->assertTrue($cancel->isSuccess());
    }

    /**
     * @test
     */
    public function ChargeToHighAmountThrowsException(): void
    {
        $authorization = $this->createPaylaterInvoiceAuthorization();
        $payment = $authorization->getPayment();
        $this->expectException(UnzerApiException::class);
        $this->unzer->performChargeOnPayment($payment, new Charge(100));
    }

    /**
     * @test
     */
    public function refundShouldBeFetchableViaUnzerObject(): void
    {
        $authorization = $this->createPaylaterInvoiceAuthorization();
        $payment = $authorization->getPayment();
        $this->unzer->performChargeOnPayment($payment, new Charge());
        $cancel = $this->unzer->cancelChargedPayment($payment, new Cancellation(33.33));

        $fetchedCancel = $this->unzer->fetchPaymentRefund($payment, $cancel->getId());

        $this->assertEquals($cancel->getShortId(), $fetchedCancel->getShortId());
        $this->assertEquals($payment->getId(), $fetchedCancel->getPayment()->getId());
        $this->assertInstanceOf(Charge::class, $fetchedCancel->getParentResource());

        $this->assertCount(1, $payment->getCancellations());
        $this->assertCount(1, $payment->getRefunds());
    }
}
