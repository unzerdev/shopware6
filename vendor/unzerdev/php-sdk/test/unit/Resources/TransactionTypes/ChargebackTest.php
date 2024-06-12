<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the Cancellation transaction type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace Resources\TransactionTypes;

use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\PaylaterInvoice;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Chargeback;
use UnzerSDK\Services\HttpService;
use UnzerSDK\Services\ResourceService;
use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\test\Fixtures\JsonProvider;
use UnzerSDK\Unzer;

class ChargebackTest extends BasePaymentTest
{
    /**
     * Verify getters and setters.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkProperly(): void
    {
        $chargeback = new Chargeback();
        $this->assertNull($chargeback->getAmount());
        $this->assertEmpty($chargeback->getPaymentReference());

        $chargeback = new Chargeback(123.4);
        $this->assertEquals(123.4, $chargeback->getAmount());

        $chargeback->setAmount(567.8);
        $this->assertEquals(567.8, $chargeback->getAmount());

        $chargeback->setPaymentReference('my Payment Reference');
        $this->assertEquals('my Payment Reference', $chargeback->getPaymentReference());
    }

    /**
     * Verify json response is handled properly
     *
     * @test
     */
    public function jsonResponseShouldBeHandledProperly()
    {
        $responseObject = json_decode(JsonProvider::getJsonFromFile('chargeback.json'));

        $chargeback = (new Chargeback())->setPayment(new Payment());
        $this->assertFalse($chargeback->isSuccess());
        $this->assertNull($chargeback->getId());
        $this->assertNull($chargeback->getUniqueId());
        $this->assertNull($chargeback->getShortId());
        $this->assertNull($chargeback->getTraceId());
        $this->assertNull($chargeback->getAmount());
        $this->assertNull($chargeback->getDate());
        $this->assertEmpty($chargeback->getMessage()->getCustomer());
        $this->assertEmpty($chargeback->getMessage()->getMerchant());

        $chargeback->handleResponse($responseObject);

        $this->assertEquals('s-cbk-1', $chargeback->getId());
        $this->assertEquals('31HA0xyz', $chargeback->getUniqueId());
        $this->assertEquals('1234.1234.1234', $chargeback->getShortId());
        $this->assertEquals('trace-123', $chargeback->getTraceId());
        $this->assertEquals(119.0000, $chargeback->getAmount());
        $this->assertEquals('2023-02-28 08:00:00', $chargeback->getDate());
        $this->assertEquals('Your payments have been successfully processed.', $chargeback->getMessage()->getCustomer());
        $this->assertEquals('Transaction succeeded', $chargeback->getMessage()->getMerchant());

        $this->assertEquals('s-pay-123', $chargeback->getPaymentId());
    }

    /**
     * verify fetching Chargeback by id without charge ID uses expected endpoint.
     *
     * @test
     */
    public function fetchChargebackByIdWithoutChargeId(): void
    {
        $responseObject = json_decode(JsonProvider::getJsonFromFile('paymentWithDirectChargeback.json'));
        $chargebackJson = JsonProvider::getJsonFromFile('chargeback.json');

        $unzer = (new Unzer('s-priv-123'));
        $payment = (new Payment())
            ->setParentResource($unzer)
            ->setId('MyPaymentId');

        // Mock http service
        $httpServiceMock = $this->getMockBuilder(HttpService::class)
            ->disableOriginalConstructor()->onlyMethods(['send'])->getMock();

        $httpServiceMock->expects($this->once())
            ->method('send')
            ->with('/payments/s-pay-329982/chargebacks/s-cbk-1')
            ->willReturn($chargebackJson);

        // Mock Resource service
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->onlyMethods(['getResource', 'fetchPayment', 'fetchPaymentType'])->getMock();
        $resourceServiceMock->method('fetchPaymentType')->willReturn(new PaylaterInvoice());
        $resourceServiceMock->expects($this->once())->method('fetchPayment')->willReturn($payment);

        $unzer->setResourceService($resourceServiceMock)
            ->setHttpService($httpServiceMock);

        $payment->handleResponse($responseObject);

        $fetchedChargeback = $unzer->fetchChargebackById('MyPaymentId', 's-cbk-1', null);
        $this->assertEquals('s-cbk-1', $fetchedChargeback->getId());
        $this->assertEquals('31HA0xyz', $fetchedChargeback->getUniqueId());
        $this->assertEquals('1234.1234.1234', $fetchedChargeback->getShortId());
        $this->assertEquals('trace-123', $fetchedChargeback->getTraceId());
        $this->assertInstanceOf(Payment::class, $fetchedChargeback->getParentResource());
    }

    /**
     * verify fetching Chargeback by id without charge ID uses expected endpoint.
     *
     * @test
     *
     * @dataProvider fetchChargebackByIdDP
     *
     * @param mixed $chargeId
     * @param mixed $expectedUri
     */
    public function fetchChargebackById($chargeId, $expectedUri): void
    {
        $responseObject = json_decode(JsonProvider::getJsonFromFile('paymentWithMultipleChargebacks.json'));
        $chargebackJson = JsonProvider::getJsonFromFile('chargeback.json');

        $unzer = (new Unzer('s-priv-123'));
        $payment = (new Payment())
            ->setParentResource($unzer);

        // Mock http service
        $httpServiceMock = $this->getMockBuilder(HttpService::class)
            ->disableOriginalConstructor()->onlyMethods(['send'])->getMock();

        $httpServiceMock->expects($this->once())
            ->method('send')
            ->with($expectedUri)
            ->willReturn($chargebackJson);

        // Mock Resource service
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->onlyMethods(['getResource', 'fetchPayment', 'fetchPaymentType'])->getMock();
        $resourceServiceMock->method('fetchPaymentType')->willReturn(new PaylaterInvoice());
        $resourceServiceMock->expects($this->once())->method('fetchPayment')->willReturn($payment);

        $unzer->setResourceService($resourceServiceMock)
            ->setHttpService($httpServiceMock);

        $payment->handleResponse($responseObject);

        $fetchedChargeback = $unzer->fetchChargebackById('s-pay-123', 's-cbk-1', $chargeId);
        $this->assertEquals('s-cbk-1', $fetchedChargeback->getId());
        $this->assertEquals('31HA0xyz', $fetchedChargeback->getUniqueId());
        $this->assertEquals('1234.1234.1234', $fetchedChargeback->getShortId());
        $this->assertEquals('trace-123', $fetchedChargeback->getTraceId());
        $this->assertInstanceOf(Charge::class, $fetchedChargeback->getParentResource());
    }

    public function fetchChargebackByIdDP(): array
    {
        return [
            'first chargeback' => [
                's-chg-1',
                '/payments/s-pay-123/charges/s-chg-1/chargebacks/s-cbk-1'
            ],
            'second chargeback' => [
                's-chg-2',
                '/payments/s-pay-123/charges/s-chg-2/chargebacks/s-cbk-1'
            ]
        ];
    }
}
