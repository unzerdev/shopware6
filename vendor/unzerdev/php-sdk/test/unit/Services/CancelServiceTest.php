<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify cancel functionality of the CancelService.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Services;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\EmbeddedResources\Amount;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Klarna;
use UnzerSDK\Resources\PaymentTypes\PaylaterInvoice;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Services\CancelService;
use UnzerSDK\Services\ResourceService;
use UnzerSDK\test\BasePaymentTest;
use PHPUnit\Framework\MockObject\MockObject;

class CancelServiceTest extends BasePaymentTest
{
    /**
     * Verify cancelAmount will call cancelAuthorizationAmount with the amountToCancel.
     * When cancelAmount is <= the value of the cancellation it Will return auth cancellation only.
     * Charge cancel will not be called if the amount to cancel has been cancelled on the authorization.
     *
     * @test
     */
    public function cancelAmountShouldCallCancelAuthorizationAmount(): void
    {
        /** @var MockObject|CancelService $cancelSrvMock */
        $cancelSrvMock = $this->getMockBuilder(CancelService::class)->disableOriginalConstructor()->setMethods(['cancelPaymentAuthorization'])->getMock();
        $this->unzer->setCancelService($cancelSrvMock);

        /** @var MockObject|Charge $chargeMock */
        $chargeMock = $this->getMockBuilder(Charge::class)->setMethods(['cancel'])->getMock();

        $payment = (new Payment($this->unzer))->setAuthorization(new Authorization(12.3));

        $cancellation = new Cancellation(12.3);
        $cancelSrvMock->expects($this->exactly(2))->method('cancelPaymentAuthorization')->willReturn($cancellation);
        $chargeMock->expects($this->never())->method('cancel');

        $this->assertEquals([$cancellation], $payment->cancelAmount(10.0));
        $this->assertEquals([$cancellation], $payment->cancelAmount(12.3));
    }

    /**
     * Verify that cancel amount will be cancelled on charges if auth does not exist.
     *
     * @test
     */
    public function chargesShouldBeCancelledIfAuthDoesNotExist1(): void
    {
        /** @var MockObject|CancelService $cancelSrvMock */
        $cancelSrvMock = $this->getMockBuilder(CancelService::class)->disableOriginalConstructor()->setMethods(['cancelPaymentAuthorization'])->getMock();
        $this->unzer->setCancelService($cancelSrvMock);

        /** @var MockObject|Charge $chargeMock */
        $chargeMock = $this->getMockBuilder(Charge::class)->setMethods(['cancel'])->setConstructorArgs([10.0])->getMock();

        $cancellation = new Cancellation(10.0);

        $cancelSrvMock->expects($this->once())->method('cancelPaymentAuthorization')->willReturn(null);
        /** @noinspection PhpParamsInspection */
        $chargeMock->expects($this->once())->method('cancel')->with(10.0, 'CANCEL')->willReturn($cancellation);

        $payment = (new Payment($this->unzer))->addCharge($chargeMock);

        $this->assertEquals([$cancellation], $payment->cancelAmount(10.0));
    }

    /**
     * Verify that cancel amount will be cancelled on charges if auth does not exist.
     *
     * @test
     */
    public function chargesShouldBeCancelledIfAuthDoesNotExist2(): void
    {
        /** @var MockObject|CancelService $cancelSrvMock */
        $cancelSrvMock = $this->getMockBuilder(CancelService::class)->disableOriginalConstructor()->setMethods(['cancelPaymentAuthorization'])->getMock();
        $this->unzer->setCancelService($cancelSrvMock);
        /** @var MockObject|Charge $charge1Mock */
        $charge1Mock = $this->getMockBuilder(Charge::class)->setMethods(['cancel'])->setConstructorArgs([10.0])->getMock();
        /** @var MockObject|Charge $charge2Mock */
        $charge2Mock = $this->getMockBuilder(Charge::class)->setMethods(['cancel'])->setConstructorArgs([12.3])->getMock();

        $cancellation1 = new Cancellation(10.0);
        $cancellation2 = new Cancellation(2.3);

        $cancelSrvMock->expects($this->exactly(3))->method('cancelPaymentAuthorization')->willReturn(null);
        $charge1Mock->expects($this->exactly(3))->method('cancel')->withConsecutive([10.0, 'CANCEL'], [null, 'CANCEL'], [null, 'CANCEL'])->willReturn($cancellation1);
        $charge2Mock->expects($this->exactly(2))->method('cancel')->withConsecutive([2.3, 'CANCEL'], [null, 'CANCEL'])->willReturn($cancellation2);

        $payment = (new Payment($this->unzer))->setAuthorization(new Authorization(12.3));
        $payment->addCharge($charge1Mock)->addCharge($charge2Mock);

        $this->assertEquals([$cancellation1], $payment->cancelAmount(10.0));
        $this->assertEquals([$cancellation1, $cancellation2], $payment->cancelAmount(12.3));
        $this->assertEquals([$cancellation1, $cancellation2], $payment->cancelAmount());
    }

    /**
     * Verify that max cancel amount is calculated correctly in edge case.
     *
     * @test
     */
    public function maxCancelAmountShouldBeRoundedCorrectly(): void
    {
        $cancelSrvMock = new CancelService($this->unzer);
        $reflection = new \ReflectionClass(get_class($cancelSrvMock));
        $method = $reflection->getMethod('calculateMaxReversalAmount');
        $method->setAccessible(true);

        $chargeAmount = 12.3;
        $receiptAmount = 10.0;
        $canceledAmount = 2.3;

        // Verify not rounded result would not equal zero.
        $this->assertNotEquals(0, $chargeAmount - $receiptAmount - $canceledAmount);

        $chargeMock = $this->getMockBuilder(Charge::class)->setMethods(['getCancelledAmount'])->setConstructorArgs([$chargeAmount])->getMock();
        $chargeMock->method('getCancelledAmount')->willReturn($canceledAmount);

        $maxReversalAmount = $method->invokeArgs($cancelSrvMock, [$chargeMock , $receiptAmount]);

        $this->assertEquals(0, $maxReversalAmount);
    }

    /**
     * Verify certain errors are allowed during cancellation and will be ignored.
     *
     * @test
     *
     * @dataProvider allowedErrorCodesDuringChargeCancel
     *
     * @param string $allowedExceptionCode
     * @param bool   $shouldHaveThrownException
     */
    public function verifyAllowedErrorsWillBeIgnoredDuringChargeCancel($allowedExceptionCode, $shouldHaveThrownException): void
    {
        /** @var MockObject|CancelService $cancelSrvMock */
        $cancelSrvMock = $this->getMockBuilder(CancelService::class)->disableOriginalConstructor()->setMethods(['cancelPaymentAuthorization'])->getMock();
        $this->unzer->setCancelService($cancelSrvMock);
        /** @var MockObject|Charge $chargeMock */
        $chargeMock = $this->getMockBuilder(Charge::class)->setMethods(['cancel'])->disableOriginalConstructor()->getMock();

        $allowedException = new UnzerApiException(null, null, $allowedExceptionCode);
        $chargeMock->method('cancel')->willThrowException($allowedException);

        $payment = (new Payment($this->unzer))->addCharge($chargeMock);

        try {
            $this->assertEquals([], $payment->cancelAmount(12.3));
            $this->assertFalse($shouldHaveThrownException, 'Exception should have been thrown here!');
        } catch (UnzerApiException $e) {
            $this->assertTrue($shouldHaveThrownException, "Exception should not have been thrown here! ({$e->getCode()})");
        }
    }

    /**
     * Verify cancelAuthorizationAmount will call cancel on the authorization and will return a list of cancels.
     *
     * @test
     */
    public function cancelAuthorizationAmountShouldCallCancelOnTheAuthorization(): void
    {
        /** @var Authorization|MockObject $authorizationMock */
        $authorizationMock = $this->getMockBuilder(Authorization::class)->setMethods(['cancel'])->getMock();
        $cancellation = new Cancellation(1.0);
        $authorizationMock->expects($this->once())->method('cancel')->willReturn($cancellation);

        /** @var Payment|MockObject $paymentMock */
        $paymentMock = $this->getMockBuilder(Payment::class)->setMethods(['getAuthorization'])->getMock();
        $paymentMock->expects($this->once())->method('getAuthorization')->willReturn($authorizationMock);
        $paymentMock->setParentResource($this->unzer)->setAuthorization($authorizationMock);

        $this->assertEquals($cancellation, $paymentMock->cancelAuthorizationAmount());
    }

    /**
     * Verify cancelAuthorizationAmount will call cancel the given amount on the authorization of the payment.
     * Cancellation amount will be the remaining amount of the payment at max.
     *
     * @test
     */
    public function cancelAuthorizationAmountShouldCallCancelWithTheRemainingAmountAtMax(): void
    {
        $cancellation = new Cancellation();

        /** @var MockObject|Authorization $authorizationMock */
        $authorizationMock = $this->getMockBuilder(Authorization::class)->setConstructorArgs([100.0])->setMethods(['cancel'])->getMock();
        $authorizationMock->expects($this->exactly(4))->method('cancel')->withConsecutive([null], [50.0], [100.0], [100.0])->willReturn($cancellation);

        /** @var Payment|MockObject $paymentMock */
        $paymentMock = $this->getMockBuilder(Payment::class)->setMethods(['getAuthorization', 'getAmount'])->getMock();
        $amountObj   = new Amount();
        $amountObj->handleResponse((object)['remaining' => 100.0]);
        $paymentMock->method('getAmount')->willReturn($amountObj);
        $paymentMock->expects($this->exactly(4))->method('getAuthorization')->willReturn($authorizationMock);
        $paymentMock->setParentResource($this->unzer);

        $paymentMock->setAuthorization($authorizationMock);
        $this->assertEquals($cancellation, $paymentMock->cancelAuthorizationAmount());
        $this->assertEquals($cancellation, $paymentMock->cancelAuthorizationAmount(50.0));
        $this->assertEquals($cancellation, $paymentMock->cancelAuthorizationAmount(100.0));
        $this->assertEquals($cancellation, $paymentMock->cancelAuthorizationAmount(101.0));
    }

    /**
     * Verify certain errors are allowed during cancellation and will be ignored.
     *
     * @test
     *
     * @dataProvider allowedErrorCodesDuringAuthCancel
     *
     * @param string $exceptionCode
     * @param bool   $shouldHaveThrownException
     */
    public function verifyAllowedErrorsWillBeIgnoredDuringAuthorizeCancel($exceptionCode, $shouldHaveThrownException): void
    {
        /** @var MockObject|Payment $paymentMock */
        $paymentMock = $this->getMockBuilder(Payment::class)->setMethods(['getAuthorization'])->getMock();

        /** @var MockObject|Authorization $authMock */
        $authMock = $this->getMockBuilder(Authorization::class)->setMethods(['cancel'])->disableOriginalConstructor()->getMock();

        $exception = new UnzerApiException(null, null, $exceptionCode);
        $authMock->method('cancel')->willThrowException($exception);
        $paymentMock->method('getAuthorization')->willReturn($authMock);
        $paymentMock->getAmount()->handleResponse((object)['remaining' => 100.0]);
        $paymentMock->setParentResource($this->unzer);

        try {
            $this->assertEquals(null, $paymentMock->cancelAuthorizationAmount(12.3));
            $this->assertFalse($shouldHaveThrownException, 'Exception should have been thrown here!');
        } catch (UnzerApiException $e) {
            $this->assertTrue($shouldHaveThrownException, "Exception should not have been thrown here! ({$e->getCode()})");
        }
    }

    /**
     * Verify cancelAuthorizationAmount will stop processing if there is no amount to cancel.
     *
     * @test
     */
    public function cancelAuthorizationAmountWillNotCallCancelIfThereIsNoOpenAmount(): void
    {
        /** @var MockObject|Payment $paymentMock */
        /** @var MockObject|Authorization $authMock */
        $paymentMock = $this->getMockBuilder(Payment::class)->setMethods(['getAuthorization'])->getMock();
        $authMock = $this->getMockBuilder(Authorization::class)->setMethods(['cancel'])->disableOriginalConstructor()->getMock();
        $paymentMock->method('getAuthorization')->willReturn($authMock);
        $authMock->expects(self::never())->method('cancel');
        $paymentMock->getAmount()->handleResponse((object)['remaining' => 0.0]);

        $paymentMock->setParentResource($this->unzer);

        $paymentMock->cancelAuthorizationAmount(12.3);
        $paymentMock->cancelAuthorizationAmount(0.0);
    }

    /**
     * Verify cancelPayment will fetch payment if the payment is referenced by paymentId.
     *
     * @test
     */
    public function paymentCancelShouldFetchPaymentIfPaymentIdIsPassed(): void
    {
        /** @var MockObject|ResourceService $resourceServiceMock */
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['fetchPayment'])->getMock();
        $cancelService = $this->unzer->setResourceService($resourceServiceMock)->getCancelService();

        $payment = (new Payment($this->unzer))->setId('paymentId');

        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects(self::once())->method('fetchPayment')->with('paymentId')->willReturn($payment);
        $cancelService->cancelPayment('paymentId');
    }

    /** Verify that payment types processed on P3 are not canceled via unzer::cancelPayment method.
     * @test
     *
     * @dataProvider p3PaymentTypes
     *
     * @param BasePaymentType $paymentType
     */
    public function cancelPaymentMethodThrowsRuntimeExceptionWithP3PaymentType(BasePaymentType $paymentType): void
    {
        $dummyPayment = new Payment($this->unzer);
        $dummyPayment->setPaymentType($paymentType);

        $this->expectException(\RuntimeException::class);
        $expectedMessage = 'The used payment type is not supported by this cancel method. Please use Unzer::cancelAuthorizedPayment() or Unzer::cancelChargedPayment() instead.';
        $this->expectExceptionMessage($expectedMessage);
        $this->unzer->cancelPayment($dummyPayment, 33.33);
    }

    //<editor-fold desc="Data Providers">

    /**
     * @return array
     */
    public function allowedErrorCodesDuringChargeCancel(): array
    {
        return [
            'already cancelled' => [ApiResponseCodes::API_ERROR_ALREADY_CANCELLED, false],
            'already charged' => [ApiResponseCodes::API_ERROR_ALREADY_CHARGED, false],
            'already chargedBack' => [ApiResponseCodes::API_ERROR_ALREADY_CHARGED_BACK, false],
            'other' => [ApiResponseCodes::API_ERROR_BASKET_ITEM_IMAGE_INVALID_URL, true]
        ];
    }

    /**
     * @return array
     */
    public function allowedErrorCodesDuringAuthCancel(): array
    {
        return [
            'already cancelled' => [ApiResponseCodes::API_ERROR_ALREADY_CANCELLED, false],
            'already chargedBack' => [ApiResponseCodes::API_ERROR_ALREADY_CHARGED, false],
            'other' => [ApiResponseCodes::API_ERROR_BASKET_ITEM_IMAGE_INVALID_URL, true]
        ];
    }

    /**
     * @return array
     */
    public function p3PaymentTypes(): array
    {
        return [
            'Paylater Invoice' => [(new PaylaterInvoice())->setId('s-piv-dummyId')],
            'Klarna' => [(new Klarna())->setId('s-piv-dummyId')]
        ];
    }

    //</editor-fold>
}
