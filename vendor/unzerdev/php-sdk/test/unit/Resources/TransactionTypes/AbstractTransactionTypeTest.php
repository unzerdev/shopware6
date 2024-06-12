<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the AbstractTransactionType.
 *
 * @link     https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\TransactionTypes;

use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Constants\LiabilityShiftIndicator;
use UnzerSDK\Constants\TransactionStatus;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Services\ResourceService;
use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\Unzer;

class AbstractTransactionTypeTest extends BasePaymentTest
{
    /**
     * Verify getters and setters work properly.
     *
     * @test
     */
    public function theGettersAndSettersShouldWorkProperly(): void
    {
        // initial check
        $payment = new Payment();
        $transactionType = new DummyTransactionType();
        $this->assertNull($transactionType->getPayment());
        $this->assertNull($transactionType->getDate());
        $this->assertNull($transactionType->getPaymentId());
        $this->assertNull($transactionType->getShortId());
        $this->assertNull($transactionType->getUniqueId());
        $this->assertNull($transactionType->getTraceId());
        $this->assertNull($transactionType->getAdditionalTransactionData());

        $this->assertFalse($transactionType->isError());
        $this->assertFalse($transactionType->isSuccess());
        $this->assertFalse($transactionType->isPending());

        $message = $transactionType->getMessage();
        $this->assertEmpty($message->getCode());
        $this->assertEmpty($message->getCustomer());

        $transactionType->setPayment($payment);
        $this->assertNull($transactionType->getRedirectUrl());

        // update
        $payment->setId('MyPaymentId');
        $date = (new DateTime('now'))->format('Y-m-d H:i:s');
        $transactionType->setDate($date);
        $ids = (object)['shortId' => 'myShortId', 'uniqueId' => 'myUniqueId', 'traceId' => 'myTraceId'];
        $responseArray = [
            'isError' => true,
            'isPending' => true,
            'isSuccess' => true,
            'processing' => $ids,
            'additionalTransactionData' => (object)['someDataKey' => 'Some Data']
        ];
        $transactionType->handleResponse((object)$responseArray);
        $messageResponse = (object)['code' => '1234', 'customer' => 'Customer message!'];
        $transactionType->handleResponse((object)['message' => $messageResponse]);

        // check again
        $this->assertSame($payment, $transactionType->getPayment());
        $this->assertSame($date, $transactionType->getDate());
        $this->assertNull($transactionType->getExternalId());
        $this->assertSame($payment->getId(), $transactionType->getPaymentId());
        $this->assertTrue($transactionType->isSuccess());
        $this->assertTrue($transactionType->isPending());
        $this->assertTrue($transactionType->isError());
        $this->assertSame('myShortId', $transactionType->getShortId());
        $this->assertSame('myUniqueId', $transactionType->getUniqueId());
        $this->assertSame('myTraceId', $transactionType->getTraceId());
        $this->assertNotNull($transactionType->getAdditionalTransactionData());
        $this->assertTrue(property_exists($transactionType->getAdditionalTransactionData(), 'someDataKey'));

        $message = $transactionType->getMessage();
        $this->assertSame('1234', $message->getCode());
        $this->assertSame('Customer message!', $message->getCustomer());
    }

    /**
     * Set status should set state flags correctly.
     *
     * @test
     */
    public function setStatusShouldSetStateFlagsCorrectly(): void
    {
        $transactionType = new DummyTransactionType();

        // Initial checks.
        $this->assertFalse($transactionType->isSuccess());
        $this->assertFalse($transactionType->isPending());
        $this->assertFalse($transactionType->isError());
        $this->assertFalse($transactionType->isResumed());

        $responseArray = ['status' => TransactionStatus::STATUS_ERROR];
        $transactionType->handleResponse((object)$responseArray);

        $this->assertFalse($transactionType->isSuccess());
        $this->assertFalse($transactionType->isPending());
        $this->assertTrue($transactionType->isError());
        $this->assertFalse($transactionType->isResumed());

        $responseArray['status'] = TransactionStatus::STATUS_SUCCESS;
        $transactionType->handleResponse((object)$responseArray);

        $this->assertTrue($transactionType->isSuccess());
        $this->assertFalse($transactionType->isPending());
        $this->assertFalse($transactionType->isError());
        $this->assertFalse($transactionType->isResumed());

        $responseArray['status'] = TransactionStatus::STATUS_PENDING;
        $transactionType->handleResponse((object)$responseArray);

        $this->assertFalse($transactionType->isSuccess());
        $this->assertTrue($transactionType->isPending());
        $this->assertFalse($transactionType->isError());
        $this->assertFalse($transactionType->isResumed());

        $responseArray['status'] = TransactionStatus::STATUS_RESUMED;
        $transactionType->handleResponse((object)$responseArray);

        $this->assertFalse($transactionType->isSuccess());
        $this->assertFalse($transactionType->isPending());
        $this->assertFalse($transactionType->isError());
        $this->assertTrue($transactionType->isResumed());

        $this->expectException(\RuntimeException::class);

        $responseArray['status'] = 'Invalid status.';
        $transactionType->handleResponse((object)$responseArray);
    }

    /**
     * Verify getters and setters work properly.
     *
     * @test
     *
     * Todo: Workaround to be removed when API sends TraceID in processing-group
     */
    public function checkTraceIdWorkaround(): void
    {
        // initial check
        $transactionType = new DummyTransactionType();
        $this->assertNull($transactionType->getTraceId());

        // update
        $transactionType->handleResponse((object)['resources' => (object)['traceId' => 'myTraceId']]);

        // check again
        $this->assertSame('myTraceId', $transactionType->getTraceId());
    }

    /**
     * Verify getRedirectUrl() calls Payment::getRedirectUrl().
     *
     * @test
     */
    public function getRedirectUrlShouldCallPaymentGetRedirectUrl(): void
    {
        $paymentMock = $this->getMockBuilder(Payment::class)->setMethods(['getRedirectUrl'])->getMock();
        $paymentMock->expects($this->once())->method('getRedirectUrl')->willReturn('https://my-redirect-url.test');

        $transactionType = new DummyTransactionType();

        /** @var Payment $paymentMock */
        $transactionType->setPayment($paymentMock);
        $this->assertEquals('https://my-redirect-url.test', $transactionType->getRedirectUrl());
    }

    /**
     * Verify abstract transaction allows for updating.
     *
     * @test
     */
    public function handleResponseShouldUpdateValuesOfAbstractTransaction(): void
    {
        $payment = (new Payment())->setId('myPaymentId');
        $transactionType = (new DummyTransactionType())->setPayment($payment);
        $this->assertNull($transactionType->getUniqueId());
        $this->assertNull($transactionType->getShortId());
        $this->assertNull($transactionType->getRedirectUrl());
        $this->assertEquals('myPaymentId', $transactionType->getPaymentId());

        $testResponse = new stdClass();
        $testResponse->uniqueId = 'myUniqueId';
        $testResponse->shortId = 'myShortId';
        $testResponse->redirectUrl = 'myRedirectUrl';
        $testResources = new stdClass();
        $testResources->paymentId = 'myNewPaymentId';
        $testResponse->resources = $testResources;
        $message = new stdClass();
        $message->code = 'myCode';
        $message->customer = 'Customer message';
        $testResponse->message = $message;
        $transactionType->handleResponse($testResponse);

        $this->assertEquals('myUniqueId', $transactionType->getUniqueId());
        $this->assertEquals('myShortId', $transactionType->getShortId());
        $this->assertEquals('myCode', $transactionType->getMessage()->getCode());
        $this->assertEquals('Customer message', $transactionType->getMessage()->getCustomer());
        $this->assertEquals('myRedirectUrl', $payment->getRedirectUrl());
        $this->assertEquals('myNewPaymentId', $payment->getId());
    }

    /**
     * Verify fetchPayment is never called after a Get-Request.
     *
     * @test
     *
     * @dataProvider updatePaymentDataProvider
     *
     * @param string  $method
     * @param integer $timesCalled
     */
    public function updatePaymentShouldOnlyBeCalledOnNotRequests($method, $timesCalled): void
    {
        $transactionTypeMock =
            $this->getMockBuilder(DummyTransactionType::class)->setMethods(['fetchPayment'])->getMock();
        $transactionTypeMock->expects($this->exactly($timesCalled))->method('fetchPayment');

        /** @var AbstractTransactionType $transactionTypeMock */
        $transactionTypeMock->handleResponse(new stdClass(), $method);
    }

    /**
     * Verify payment object is fetched on fetchPayment call using the Unzer resource service object.
     *
     * @test
     */
    public function fetchPaymentShouldFetchPaymentObject(): void
    {
        $payment = (new Payment())->setId('myPaymentId');

        /** @var ResourceService|MockObject $resourceServiceMock */
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['fetchResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('fetchResource')->with($payment);

        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);

        $transactionType = (new DummyTransactionType())->setPayment($payment);
        $transactionType->fetchPayment();
    }

    /**
     * Liability indicator response should stored in transaction
     *
     * @test
     */
    public function liabilityResponseShouldBeStroedInTransaction()
    {
        $jsonRespone = '{"additionalTransactionData":{"card":{"liability":"MERCHANT"}}}';

        $transaction = new DummyTransactionType();
        $transaction->handleResponse(json_decode($jsonRespone, false));
        $this->assertEquals($transaction->getCardTransactionData()->getLiability(), LiabilityShiftIndicator::MERCHANT);

        $jsonRespone = '{"additionalTransactionData":{"card":{"liability":"ISSUER"}}}';
        $transaction->handleResponse(json_decode($jsonRespone, false));
        $this->assertEquals($transaction->getCardTransactionData()->getLiability(), LiabilityShiftIndicator::ISSUER);
    }

    //<editor-fold desc="Data Providers">

    /**
     * DataProvider for updatePaymentShouldOnlyBeCalledOnGetRequests.
     *
     * @return array
     */
    public function updatePaymentDataProvider(): array
    {
        return [
            HttpAdapterInterface::REQUEST_GET => [HttpAdapterInterface::REQUEST_GET, 0],
            HttpAdapterInterface::REQUEST_POST => [HttpAdapterInterface::REQUEST_POST, 1],
            HttpAdapterInterface::REQUEST_PUT => [HttpAdapterInterface::REQUEST_PUT, 1],
            HttpAdapterInterface::REQUEST_DELETE => [HttpAdapterInterface::REQUEST_DELETE, 1]
        ];
    }

    //</editor-fold>
}
