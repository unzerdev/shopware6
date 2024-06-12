<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the Payment resource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources;

use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use stdClass;
use UnzerSDK\Constants\PaymentState;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\Amount;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Paypage;
use UnzerSDK\Resources\PaymentTypes\Sofort;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Chargeback;
use UnzerSDK\Resources\TransactionTypes\Payout;
use UnzerSDK\Resources\TransactionTypes\Shipment;
use UnzerSDK\Services\ResourceService;
use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\test\Fixtures\JsonProvider;
use UnzerSDK\Unzer;

class PaymentTest extends BasePaymentTest
{
    /**
     * Verify getters and setters work properly.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkProperly(): void
    {
        // initial check
        $payment = (new Payment())->setParentResource(new Unzer('s-priv-1234'));
        $this->assertNull($payment->getRedirectUrl());
        $this->assertNull($payment->getCustomer());
        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(Amount::class, $payment->getAmount());
        $this->assertNull($payment->getTraceId());
        $this->assertNull($payment->getAuthorization());
        $this->assertIsEmptyArray($payment->getReversals());
        $this->assertIsEmptyArray($payment->getRefunds());

        // update
        $ids = (object)['traceId' => 'myTraceId'];
        $payment->handleResponse((object)['redirectUrl' => 'https://my-redirect-url.test', 'processing' => $ids]);
        $authorize = new Authorization();
        $payment->setAuthorization($authorize);
        $payout = new Payout();
        $payment->setPayout($payout);

        // check
        $this->assertEquals('https://my-redirect-url.test', $payment->getRedirectUrl());
        $this->assertSame($authorize, $payment->getAuthorization(true));
        $this->assertSame($payout, $payment->getPayout(true));
        $this->assertSame('myTraceId', $payment->getTraceId());
    }

    /**
     * Verify that direct payment cancellations are handled correctly.
     *
     * @test
     */
    public function PaymentCancellationsShouldBeHandledAsExpected()
    {
        $payment = (new Payment())->setParentResource(new Unzer('s-priv-1234'));

        $this->assertIsEmptyArray($payment->getReversals());
        $this->assertIsEmptyArray($payment->getRefunds());

        $transactions = [
            (object)[
                "date" => "2022-05-13 08:04:29",
                "type" => "authorize",
                "status" => "success",
                "url" => "https://api.unzer.com/v1/payments/s-pay-777/authorize/s-aut-1",
                "amount" => "99.9900"
            ],
            (object)[
                "date" => "2022-05-13 08:04:30",
                "type" => "charge",
                "status" => "success",
                "url" => "https://api.unzer.com/v1/payments/s-pay-777/charges/s-chg-1",
                "amount" => "99.9900"
            ],
            (object)[
                "date" => "2022-05-13 08:04:31",
                "type" => "cancel-authorize",
                "status" => "success",
                "url" => "https://api.unzer.com/v1/payments/s-pay-777/authorize/cancels/s-cnl-1",
                "amount" => "22.2200"
            ],
            (object)[
                "date" => "2022-05-13 08:04:31",
                "type" => "cancel-charge",
                "status" => "success",
                "url" => "https://api.unzer.com/v1/payments/s-pay-777/charges/cancels/s-cnl-1",
                "amount" => "22.2200"
            ]
        ];

        $payment->handleResponse((object)['transactions' => $transactions]);

        $this->assertCount(1, $payment->getReversals());
        $this->assertCount(1, $payment->getRefunds());
        $this->assertCount(2, $payment->getCancellations());
    }

    /**
     * Verify that adding refunds/reversals to the payment happens as expected
     *
     * @test
     */
    public function verifyAddingCancelationsWorksProperly()
    {
        $payment = (new Payment())->setParentResource(new Unzer('s-priv-1234'));
        $this->assertIsEmptyArray($payment->getReversals());
        $this->assertIsEmptyArray($payment->getRefunds());
        $reversal1 = (new Cancellation())->setId('s-cnl-1')->setAmount(99.99);
        $reversal2 = (new Cancellation())->setId('s-cnl-2')->setAmount(99.99);
        $reversal3 = (new Cancellation())->setId('s-cnl-2')->setAmount(33.33);

        $payment->addReversal($reversal1);
        $payment->addReversal($reversal2);
        $this->assertCount(2, $payment->getReversals());

        // update existing transaction.
        $payment->addReversal($reversal3);
        $this->assertCount(2, $payment->getReversals());

        $this->assertEquals(33.33, $payment->getReversals()['s-cnl-2']->getAmount());
    }

    /**
     * @test
     *
     * Todo: Workaround to be removed when API sends TraceID in processing-group
     */
    public function checkTraceIdWorkaround(): void
    {
        // initial check
        $payment = (new Payment())->setParentResource(new Unzer('s-priv-1234'));
        $this->assertNull($payment->getTraceId());

        // update
        $payment->handleResponse((object)['resources' => (object)['traceId' => 'myTraceId']]);

        // check
        $this->assertSame('myTraceId', $payment->getTraceId());
    }

    /**
     * Verify getAuthorization should try to fetch resource if lazy loading is off and the authorization is not null.
     *
     * @test
     */
    public function getAuthorizationShouldFetchAuthorizeIfNotLazyAndAuthIsNotNull(): void
    {
        $payment = (new Payment())->setId('myPaymentId');
        $authorization = new Authorization();
        $payment->setAuthorization($authorization);

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('getResource')->with($authorization);

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);

        $payment->getAuthorization();
    }

    /**
     * Verify getAuthorization should try to fetch resource if lazy loading is off and the authorization is not null.
     *
     * @test
     */
    public function getAuthorizationShouldNotFetchAuthorizeIfNotLazyAndAuthIsNull(): void
    {
        $payment = (new Payment())->setId('myPaymentId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->never())->method('getResource');

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);

        $payment->getAuthorization();
    }

    /**
     * Verify Charge array is handled properly.
     *
     * @test
     */
    public function chargesShouldBeHandledProperly(): void
    {
        $payment = new Payment();
        $this->assertIsEmptyArray($payment->getCharges());

        $charge1 = (new Charge())->setId('firstCharge');
        $charge2 = (new Charge())->setId('secondCharge');

        $chargeArray[] = $charge1;
        $payment->addCharge($charge1);
        $this->assertEquals($chargeArray, $payment->getCharges());

        $chargeArray[] = $charge2;
        $payment->addCharge($charge2);
        $this->assertEquals($chargeArray, $payment->getCharges());

        $this->assertSame($charge2, $payment->getCharge('secondCharge', true));
        $this->assertSame($charge1, $payment->getCharge('firstCharge', true));

        $this->assertSame($charge1, $payment->getChargeByIndex(0, true));
        $this->assertSame($charge2, $payment->getChargeByIndex(1, true));
    }

    /**
     * Verify getChargeById will fetch the Charge if lazy loading is off and the charge exists.
     *
     * @test
     */
    public function getChargeByIdShouldFetchChargeIfItExistsAndLazyLoadingIsOff(): void
    {
        $payment = (new Payment())->setId('myPaymentId');
        $charge1 = (new Charge())->setId('firstCharge');
        $charge2 = (new Charge())->setId('secondCharge');

        $payment->addCharge($charge1);
        $payment->addCharge($charge2);

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->exactly(2))
            ->method('getResource')
            ->withConsecutive([$charge1], [$charge2]);

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);

        $payment->getCharge('firstCharge');
        $payment->getCharge('secondCharge');
    }

    /**
     * Verify getCharge will fetch the Charge if lazy loading is off and the charge exists.
     *
     * @test
     */
    public function getChargeShouldFetchChargeIfItExistsAndLazyLoadingIsOff(): void
    {
        $payment = (new Payment())->setId('myPaymentId');
        $charge1 = (new Charge())->setId('firstCharge');
        $charge2 = (new Charge())->setId('secondCharge');

        $payment->addCharge($charge1);
        $payment->addCharge($charge2);

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->exactly(2))
            ->method('getResource')
            ->withConsecutive([$charge1], [$charge2]);

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);

        $payment->getChargeByIndex(0);
        $payment->getChargeByIndex(1);
    }

    /**
     * Verify getCharge and getChargeById will return null if the Charge does not exist.
     *
     * @test
     */
    public function getChargeMethodsShouldReturnNullIfTheChargeIdUnknown(): void
    {
        $payment = (new Payment())->setId('myPaymentId');
        $charge1 = (new Charge())->setId('firstCharge');
        $charge2 = (new Charge())->setId('secondCharge');
        $payment->addCharge($charge1);
        $payment->addCharge($charge2);

        $this->assertSame($charge1, $payment->getCharge('firstCharge', true));
        $this->assertSame($charge2, $payment->getCharge('secondCharge', true));
        $this->assertNull($payment->getCharge('thirdCharge'));

        $this->assertSame($charge1, $payment->getChargeByIndex(0, true));
        $this->assertSame($charge2, $payment->getChargeByIndex(1, true));
        $this->assertNull($payment->getChargeByIndex(2));
    }

    /**
     * Verify getPayout should try to fetch resource if lazy loading is off and the authorization is not null.
     *
     * @test
     */
    public function getPayoutShouldFetchPayoutIfNotLazyAndPayoutIsNotNull(): void
    {
        $payment = (new Payment())->setId('myPaymentId');
        $payout = new Payout();
        $payment->setPayout($payout);

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('getResource')->with($payout);

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);

        $payment->getPayout();
    }

    /**
     * Verify getPayout should try to fetch resource if lazy loading is off and the payout is not null.
     *
     * @test
     */
    public function getPayoutShouldNotFetchPayoutIfNotLazyAndPayoutIsNull(): void
    {
        $payment = (new Payment())->setId('myPaymentId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->never())->method('getResource');

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);

        $payment->getPayout();
    }

    /**
     * Verify setCustomer does nothing if the passed customer is empty.
     *
     * @test
     */
    public function setCustomerShouldDoNothingIfTheCustomerIsEmpty(): void
    {
        $unzerObj = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzerObj);
        $customer = CustomerFactory::createCustomer('Max', 'Mustermann')->setId('myCustomer');
        $payment->setCustomer($customer);

        $this->assertSame($customer, $payment->getCustomer());

        $payment->setCustomer(0);
        $this->assertSame($customer, $payment->getCustomer());

        $payment->setCustomer(null);
        $this->assertSame($customer, $payment->getCustomer());
    }

    /**
     * Verify setCustomer will try to fetch the customer if it is passed as string (i. e. id).
     *
     * @test
     */
    public function setCustomerShouldFetchCustomerIfItIsPassedAsIdString(): void
    {
        $payment = (new Payment())->setId('myPaymentId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['fetchCustomer'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('fetchCustomer')->with('MyCustomerId');

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);

        $payment->setCustomer('MyCustomerId');
    }

    /**
     * Verify setCustomer will create the resource if it is passed as object without id.
     *
     * @test
     */
    public function setCustomerShouldCreateCustomerIfItIsPassedAsObjectWithoutId(): void
    {
        $payment = (new Payment())->setId('myPaymentId');
        $customer = new Customer();

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['createCustomer'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('createCustomer')->with($customer);

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);

        $payment->setCustomer($customer);
    }

    /**
     * Verify setPaymentType will do nothing if the paymentType is empty.
     *
     * @test
     */
    public function setPaymentTypeShouldDoNothingIfThePaymentTypeIsEmpty(): void
    {
        $unzerObj = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzerObj);
        $paymentType = (new Sofort())->setId('123');

        $payment->setPaymentType($paymentType);
        $this->assertSame($paymentType, $payment->getPaymentType());

        $payment->setPaymentType(0);
        $this->assertSame($paymentType, $payment->getPaymentType());

        $payment->setPaymentType(null);
        $this->assertSame($paymentType, $payment->getPaymentType());
    }

    /**
     * Verify setPaymentType will try to fetch the payment type if it is passed as string (i. e. id).
     *
     * @test
     */
    public function setPaymentTypeShouldFetchResourceIfItIsPassedAsIdString(): void
    {
        $payment = (new Payment())->setId('myPaymentId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['fetchPaymentType'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('fetchPaymentType')->with('MyPaymentId');

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);

        $payment->setPaymentType('MyPaymentId');
    }

    /**
     * Verify setCustomer will create the resource if it is passed as object without id.
     *
     * @test
     */
    public function setPaymentTypeShouldCreateResourceIfItIsPassedAsObjectWithoutId(): void
    {
        $payment = (new Payment())->setId('myPaymentId');
        $paymentType = new Sofort();

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['createPaymentType'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('createPaymentType')->with($paymentType);

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);

        $payment->setPaymentType($paymentType);
    }

    /**
     * Verify getCancellations will call getCancellations on all Charge and Authorization objects to fetch its refunds.
     *
     * @test
     */
    public function getCancellationsShouldCollectAllCancellationsOfCorrespondingTransactions(): void
    {
        $payment = new Payment();
        $cancellation1 = (new Cancellation())->setId('cancellation1');
        $cancellation2 = (new Cancellation())->setId('cancellation2');
        $cancellation3 = (new Cancellation())->setId('cancellation3');
        $cancellation4 = (new Cancellation())->setId('cancellation4');

        $expectedCancellations = [];

        $this->assertEquals($expectedCancellations, $payment->getCancellations());

        $authorize = $this->getMockBuilder(Authorization::class)->setMethods(['getCancellations'])->getMock();
        $authorize->expects($this->exactly(4))->method('getCancellations')->willReturn([$cancellation1]);

        /** @var Authorization $authorize */
        $payment->setAuthorization($authorize);
        $expectedCancellations[] = $cancellation1;
        $this->assertEquals($expectedCancellations, $payment->getCancellations());

        $charge1 = $this->getMockBuilder(Charge::class)->setMethods(['getCancellations'])->getMock();
        $charge1->expects($this->exactly(3))->method('getCancellations')->willReturn([$cancellation2]);

        /** @var Charge $charge1 */
        $payment->addCharge($charge1);
        $expectedCancellations[] = $cancellation2;
        $this->assertEquals($expectedCancellations, $payment->getCancellations());

        $charge2 = $this->getMockBuilder(Charge::class)->setMethods(['getCancellations'])->getMock();
        $charge2->expects($this->exactly(2))->method('getCancellations')->willReturn([$cancellation3, $cancellation4]);

        /** @var Charge $charge2 */
        $payment->addCharge($charge2);
        $expectedCancellations[] = $cancellation3;
        $expectedCancellations[] = $cancellation4;
        $this->assertEquals($expectedCancellations, $payment->getCancellations());

        $charge3 = $this->getMockBuilder(Charge::class)->setMethods(['getCancellations'])->getMock();
        $charge3->expects($this->once())->method('getCancellations')->willReturn([]);

        /** @var Charge $charge3 */
        $payment->addCharge($charge3);
        $this->assertEquals($expectedCancellations, $payment->getCancellations());
    }

    /**
     * Verify getCancellation calls getCancellations and returns null if cancellation does not exist.
     *
     * @deprecated To be removed with Payment::getCancellation()
     *
     * @test
     */
    public function getCancellationShouldCallGetCancellationsAndReturnNullIfNoCancellationExists(): void
    {
        $paymentMock = $this->getMockBuilder(Payment::class)->setMethods(['getCancellations'])->getMock();
        $paymentMock->expects($this->once())->method('getCancellations')->willReturn([]);

        /** @var Payment $paymentMock */
        $this->assertNull($paymentMock->getCancellation('123'));
    }

    /**
     * Verify getCancellation returns cancellation if it exists.
     *
     * @deprecated To be removed with Payment::getCancellation()
     *
     * @test
     */
    public function getCancellationShouldReturnCancellationIfItExists(): void
    {
        $cancellation1 = (new Cancellation())->setId('cancellation1');
        $cancellation2 = (new Cancellation())->setId('cancellation2');
        $cancellation3 = (new Cancellation())->setId('cancellation3');
        $cancellations = [$cancellation1, $cancellation2, $cancellation3];

        $paymentMock = $this->getMockBuilder(Payment::class)->setMethods(['getCancellations'])->getMock();
        $paymentMock->expects($this->once())->method('getCancellations')->willReturn($cancellations);

        /** @var Payment $paymentMock */
        $this->assertSame($cancellation2, $paymentMock->getCancellation('cancellation2', true));
    }

    /**
     * Verify getCancellation fetches cancellation if it exists and lazy loading is false.
     *
     * @deprecated To be removed with Payment::getCancellation()
     *
     * @test
     */
    public function getCancellationShouldReturnCancellationIfItExistsAndFetchItIfNotLazy(): void
    {
        $cancellation = (new Cancellation())->setId('cancellation123');

        $paymentMock = $this->getMockBuilder(Payment::class)->setMethods(['getCancellations'])->getMock();
        $paymentMock->expects($this->exactly(2))->method('getCancellations')->willReturn([$cancellation]);

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('getResource')->with($cancellation);

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);

        /** @var Payment $paymentMock */
        $paymentMock->setParentResource($unzerObj);

        $this->assertSame($cancellation, $paymentMock->getCancellation('cancellation123'));
        $this->assertNull($paymentMock->getCancellation('cancellation1234'));
    }

    /**
     * Verify Shipments are handled properly.
     *
     * @test
     */
    public function shipmentsShouldBeHandledProperly(): void
    {
        $payment = new Payment();
        $this->assertIsEmptyArray($payment->getShipments());

        $shipment1 = (new Shipment())->setId('firstShipment');
        $shipment2 = (new Shipment())->setId('secondShipment');

        $shipArray[] = $shipment1;
        $payment->addShipment($shipment1);
        $this->assertEquals($shipArray, $payment->getShipments());

        $shipArray[] = $shipment2;
        $payment->addShipment($shipment2);
        $this->assertEquals($shipArray, $payment->getShipments());

        $this->assertSame($shipment2, $payment->getShipment('secondShipment', true));
        $this->assertSame($shipment1, $payment->getShipment('firstShipment', true));
    }

    /**
     * Verify getCancellation fetches cancellation if it exists and lazy loading is false.
     *
     * @test
     */
    public function getShipmentByIdShouldReturnShipmentIfItExistsAndFetchItIfNotLazy(): void
    {
        $shipment = (new Shipment())->setId('shipment123');

        $paymentMock = $this->getMockBuilder(Payment::class)->setMethods(['getShipments'])->getMock();
        $paymentMock->expects($this->exactly(2))->method('getShipments')->willReturn([$shipment]);

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('getResource')->with($shipment);

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);

        /** @var Payment $paymentMock */
        $paymentMock->setParentResource($unzerObj);

        $this->assertSame($shipment, $paymentMock->getShipment('shipment123'));
        $this->assertNull($paymentMock->getShipment('shipment1234'));
    }

    /**
     * Verify the currency is fetched from the amount object.
     *
     * @test
     */
    public function getAndSetCurrencyShouldPropagateToTheAmountObject(): void
    {
        /** @var Amount|MockObject $amountMock */
        $amountMock = $this->getMockBuilder(Amount::class)->setMethods(['getCurrency', 'setCurrency'])->getMock();
        $amountMock->expects($this->once())->method('getCurrency')->willReturn('MyTestGetCurrency');
        /** @noinspection PhpParamsInspection */
        $amountMock->expects($this->once())->method('setCurrency')->with('MyTestSetCurrency');

        $payment = (new Payment())->setAmount($amountMock);
        $payment->handleResponse((object) ['currency' => 'MyTestSetCurrency']);
        $this->assertEquals('MyTestGetCurrency', $payment->getCurrency());
    }

    /**
     * Verify that a payment that contains a cancel-authorize transaction can be fetched, even though no authorize
     * transaction exists. Can occur when canceling via Insights.
     *
     * @test
     */
    public function cancelAuthorizeOnInvoiceShouldBeHandledCorrectly(): void
    {
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        /** @noinspection PhpParamsInspection */

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment = new Payment();
        $payment->setParentResource($unzerObj);

        $response = (object)[
            "id" => "s-pay-666",
            "state" => (object)["id" => 1, "name" => "completed"],
            "currency" => "EUR",
            "transactions" => [
                (object)["date" => "2021-11-17 11:47:07",
                    "type" => "charge",
                    "status" => "pending",
                    "url" => "https://api.unzer.com/v1/payments/s-pay-666/charges/s-chg-1", "amount" => "14.9900"
                ],
                (object)[
                    "date" => "2021-11-17 11:48:52",
                    "type" => "shipment", "status" => "success",
                    "url" => "https://api.unzer.com/v1/payments/s-pay-666/shipments/s-shp-1",
                    "amount" => "14.9900"],
                (object)["date" => "2021-11-17 11:48:52",
                    "type" => "cancel-authorize",
                    "status" => "success",
                    "url" => "https://api.unzer.com/v1/payments/s-pay-666/charges/s-chg-1/cancels/s-cnl-1",
                    "amount" => "10.0000"]
            ]
        ];
        $payment->handleResponse($response);
        $this->assertNull($payment->getAuthorization());
        /** @var Charge $initialCharge */
        $initialCharge = $payment->getCharges()[0];

        $this->assertCount(1, $initialCharge->getCancellations());
    }

    //<editor-fold desc="Handle Response Tests">

    /**
     * Verify handleResponse will update stateId.
     *
     * @test
     *
     * @dataProvider stateDataProvider
     *
     * @param integer $state
     */
    public function handleResponseShouldUpdateStateId($state): void
    {
        $payment = new Payment();
        $this->assertEquals(PaymentState::STATE_PENDING, $payment->getState());

        $response = new stdClass();
        $response->state = new stdClass();
        $response->state->id = $state;
        $payment->handleResponse($response);
        $this->assertEquals($state, $payment->getState());
    }

    /**
     * Verify handleResponse updates payment id.
     *
     * @test
     */
    public function handleResponseShouldUpdatePaymentId(): void
    {
        $payment = (new Payment())->setId('MyPaymentId');
        $this->assertEquals('MyPaymentId', $payment->getId());

        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->paymentId = 'MyNewPaymentId';
        $payment->handleResponse($response);
        $this->assertEquals('MyNewPaymentId', $payment->getId());
    }

    /**
     * Verify handleResponse fetches Customer if it is not set.
     *
     * @test
     */
    public function handleResponseShouldFetchCustomerIfItIsNotSet(): void
    {
        $payment = (new Payment())->setId('myPaymentId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['fetchCustomer'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('fetchCustomer')->with('MyNewCustomerId');

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);

        $this->assertNull($payment->getCustomer());

        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->customerId = 'MyNewCustomerId';
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse updates customer if it set.
     *
     * @test
     */
    public function handleResponseShouldFetchAndUpdateCustomerIfItIsAlreadySet(): void
    {
        $payment = (new Payment())->setId('myPaymentId');
        $customer = (new Customer())->setId('customerId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('getResource')->with($customer);

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);
        $payment->setCustomer($customer);

        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->customerId = 'customerId';
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse updates payPage if it set.
     *
     * @test
     */
    public function handleResponseShouldFetchAndUpdatePayPageIfItIsAlreadySet(): void
    {
        $payment = (new Payment())->setId('myPaymentId');
        $payPage = (new Paypage(0, '', ''))->setId('payPageId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['fetchResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->never())->method('fetchResource')->with($payPage);

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);
        $payment->setpayPage($payPage);

        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->payPageId = 'payPageId';
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse updates paymentType.
     *
     * @test
     */
    public function handleResponseShouldFetchAndUpdatePaymentTypeIfTheIdIsSet(): void
    {
        $payment = (new Payment())->setId('myPaymentId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['fetchPaymentType'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('fetchPaymentType')->with('PaymentTypeId');

        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($unzerObj);

        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->typeId = 'PaymentTypeId';
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse updates metadata.
     *
     * @test
     */
    public function handleResponseShouldFetchAndUpdateMetadataIfTheIdIsSet(): void
    {
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['fetchMetadata'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('fetchMetadata')->with('MetadataId');
        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment = (new Payment())->setId('myPaymentId')->setParentResource($unzerObj);

        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->metadataId = 'MetadataId';
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse updates metadata.
     *
     * @test
     */
    public function handleResponseShouldGetMetadataIfUnfetchedMetadataObjectWithIdIsGiven(): void
    {
        $metadata = (new Metadata())->setId('MetadataId');
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('getResource')->with($metadata);
        /** @var ResourceService $resourceServiceMock */
        $unzerObj = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment = (new Payment())->setId('myPaymentId')->setParentResource($unzerObj)->setMetadata($metadata);

        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->metadataId = 'MetadataId';
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse does nothing if transactions is empty.
     *
     * @test
     */
    public function handleResponseShouldUpdateChargeTransactions(): void
    {
        /** @var Payment $payment */
        $payment = (new Payment())->setId('MyPaymentId');
        $this->assertIsEmptyArray($payment->getCharges());
        $this->assertIsEmptyArray($payment->getShipments());
        $this->assertIsEmptyArray($payment->getCancellations());
        $this->assertNull($payment->getAuthorization());

        $response = new stdClass();
        $response->transactions = [];
        $payment->handleResponse($response);

        $this->assertIsEmptyArray($payment->getCharges());
        $this->assertIsEmptyArray($payment->getShipments());
        $this->assertIsEmptyArray($payment->getCancellations());
        $this->assertIsEmptyArray($payment->getChargebacks());
        $this->assertNull($payment->getPayout());
        $this->assertNull($payment->getAuthorization());
    }

    /**
     * Verify handleResponse updates existing authorization from response.
     *
     * @test
     */
    public function handleResponseShouldUpdateAuthorizationFromResponse(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');

        $authorization = (new Authorization(11.98, 'EUR'))->setId('s-aut-1');
        $this->assertEquals(11.98, $authorization->getAmount());

        $payment->setAuthorization($authorization);

        $authorizationData = new stdClass();
        $authorizationData->url = 'https://api-url.test/payments/MyPaymentId/authorize/s-aut-1';
        $authorizationData->amount = '10.321';
        $authorizationData->type = 'authorize';

        $response = new stdClass();
        $response->transactions = [$authorizationData];
        $payment->handleResponse($response);

        $authorization = $payment->getAuthorization(true);
        $this->assertInstanceOf(Authorization::class, $authorization);
        $this->assertEquals(10.321, $authorization->getAmount());
    }

    /**
     * Verify handleResponse updates existing chargebacks from response.
     *
     * @test
     */
    public function handleResponseShouldUpdateChargebackFromResponse(): void
    {
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->onlyMethods(['getResource'])->getMock();

        $unzer = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment = (new Payment())
            ->setParentResource($unzer)
            ->setId('MyPaymentId');

        $paymentJson = JsonProvider::getJsonFromFile('paymentWithMultipleChargebacks.json');
        $response = new stdClass();

        $payment->handleResponse(json_decode($paymentJson));

        $chargebacks = $payment->getChargebacks(true);
        $charges = $payment->getCharges();
        $this->assertCount(2, $charges);
        $this->assertCount(2, $chargebacks);

        $chargeback1 = $chargebacks[0];
        $this->assertInstanceOf(Chargeback::class, $chargeback1);
        $this->assertInstanceOf(Charge::class, $chargeback1->getParentResource());
        $this->assertEquals(0.5, $chargeback1->getAmount());

        $chargeback2 = $chargebacks[1];
        $this->assertInstanceOf(Chargeback::class, $chargeback2);
        $this->assertInstanceOf(Charge::class, $chargeback2->getParentResource());
        $this->assertEquals(0.5, $chargeback2->getAmount());

        // Charges contain chargeback reference.
        $charge1 = $charges[0];
        $this->assertCount(1, $charge1->getChargebacks());

        $charge2 = $charges[1];
        $this->assertCount(1, $charge1->getChargebacks());
    }

    /**
     * Verify handleResponse updates existing chargebacks from response.
     *
     * @test
     */
    public function chargebackOnPaymentShouldBeHandledProperly(): void
    {
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->onlyMethods(['getResource', 'fetchResource'])->getMock();

        $unzer = (new Unzer('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment = (new Payment())
            ->setParentResource($unzer)
            ->setId('MyPaymentId');

        $paymentResponse = JsonProvider::getJsonFromFile('paymentWithDirectChargeback.json');
        $payment->handleResponse(json_decode($paymentResponse, false));

        $chargebacks = $payment->getChargebacks(true);
        $charges = $payment->getCharges();
        $this->assertCount(2, $charges);
        $this->assertCount(2, $chargebacks);

        $chargeback1 = $chargebacks[0];
        $this->assertInstanceOf(Chargeback::class, $chargeback1);
        $this->assertInstanceOf(Payment::class, $chargeback1->getParentResource());
        $this->assertEquals(0.5, $chargeback1->getAmount());

        $chargeback2 = $chargebacks[1];
        $this->assertInstanceOf(Chargeback::class, $chargeback2);
        $this->assertInstanceOf(Payment::class, $chargeback2->getParentResource());
        $this->assertEquals(0.5, $chargeback2->getAmount());

        // Charges contain chargeback reference.
        $charge1 = $charges[0];
        $this->assertCount(0, $charge1->getChargebacks());

        $charge2 = $charges[1];
        $this->assertCount(0, $charge2->getChargebacks());
    }

    /**
     * Verify handleResponse adds authorization from response.
     *
     * @test
     */
    public function handleResponseShouldAddAuthorizationFromResponse(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');
        $this->assertNull($payment->getAuthorization());

        $authorizationData = new stdClass();
        $authorizationData->url = 'https://api-url.test/payments/MyPaymentId/authorize/s-aut-1';
        $authorizationData->amount = '10.123';
        $authorizationData->type = 'authorize';

        $response = new stdClass();
        $response->transactions = [$authorizationData];
        $payment->handleResponse($response);

        $authorization = $payment->getAuthorization(true);
        $this->assertInstanceOf(Authorization::class, $authorization);
        $this->assertEquals('s-aut-1', $authorization->getId());
        $this->assertEquals(10.123, $authorization->getAmount());
        $this->assertSame($payment, $authorization->getPayment());
        $this->assertSame($payment, $authorization->getParentResource());
    }

    /**
     * Verify handleResponse updates existing charge from response.
     *
     * @test
     */
    public function handleResponseShouldUpdateChargeFromResponseIfItExists(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');

        $charge1 = (new Charge(11.98, 'EUR'))->setId('s-chg-1');
        $charge2 = (new Charge(22.98, 'EUR'))->setId('s-chg-2');
        $this->assertEquals(22.98, $charge2->getAmount());

        $payment->addCharge($charge1)->addCharge($charge2);

        $chargeData = new stdClass();
        $chargeData->url = 'https://api-url.test/payments/MyPaymentId/charge/s-chg-2';
        $chargeData->amount = '11.111';
        $chargeData->type = 'charge';

        $response = new stdClass();
        $response->transactions = [$chargeData];
        $payment->handleResponse($response);

        $charge = $payment->getCharge('s-chg-2', true);
        $this->assertInstanceOf(Charge::class, $charge);
        $this->assertSame($charge2, $charge);
        $this->assertEquals(11.111, $charge->getAmount());
    }

    /**
     * Verify handleResponse adds non existing charge from response.
     *
     * @test
     */
    public function handleResponseShouldAddChargeFromResponseIfItDoesNotExists(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');

        $charge1 = (new Charge(11.98, 'EUR'))->setId('s-chg-1');
        $payment->addCharge($charge1);
        $this->assertCount(1, $payment->getCharges());
        $this->assertNull($payment->getCharge('s-chg-2'));

        $chargeData = new stdClass();
        $chargeData->url = 'https://api-url.test/payments/MyPaymentId/charge/s-chg-2';
        $chargeData->amount = '11.111';
        $chargeData->type = 'charge';

        $response = new stdClass();
        $response->transactions = [$chargeData];
        $payment->handleResponse($response);

        $charge = $payment->getCharge('s-chg-2', true);
        $this->assertInstanceOf(Charge::class, $charge);
        $this->assertCount(2, $payment->getCharges());
        $this->assertEquals(11.111, $charge->getAmount());
    }

    /**
     * Verify handleResponse updates existing reversals from response.
     *
     * @test
     */
    public function handleResponseShouldUpdateReversalFromResponseIfItExists(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');
        $authorize = (new Authorization(23.55, 'EUR'))->setId('s-aut-1');
        $payment->setAuthorization($authorize);
        $reversal1 = (new Cancellation(1.98))->setId('s-cnl-1');
        $reversal2 = (new Cancellation(2.98))->setId('s-cnl-2');
        $this->assertEquals(2.98, $reversal2->getAmount());
        $authorize->addCancellation($reversal1)->addCancellation($reversal2);

        $cancellation = new stdClass();
        $cancellation->url = 'https://api-url.test/payments/MyPaymentId/authorize/s-aut-1/cancel/s-cnl-2';
        $cancellation->amount = '11.111';
        $cancellation->type = 'cancel-authorize';

        $response = new stdClass();
        $response->transactions = [$cancellation];
        $payment->handleResponse($response);

        $authorization = $payment->getAuthorization(true);
        $cancellation = $authorization->getCancellation('s-cnl-2', true);
        $this->assertInstanceOf(Cancellation::class, $cancellation);
        $this->assertSame($reversal2, $cancellation);
        $this->assertEquals(11.111, $cancellation->getAmount());
    }

    /**
     * Verify handleResponse adds non existing reversal from response.
     *
     * @test
     */
    public function handleResponseShouldAddReversalFromResponseIfItDoesNotExists(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');
        $authorize = (new Authorization(23.55, 'EUR'))->setId('s-aut-1');
        $payment->setAuthorization($authorize);
        $reversal1 = (new Cancellation(1.98))->setId('s-cnl-1');
        $authorize->addCancellation($reversal1);
        $this->assertNull($authorize->getCancellation('s-cnl-2'));
        $this->assertCount(1, $authorize->getCancellations());


        $cancellation = new stdClass();
        $cancellation->url = 'https://api-url.test/payments/MyPaymentId/authorize/s-aut-1/cancel/s-cnl-2';
        $cancellation->amount = '11.111';
        $cancellation->type = 'cancel-authorize';

        $response = new stdClass();
        $response->transactions = [$cancellation];
        $payment->handleResponse($response);

        $authorization = $payment->getAuthorization(true);
        $cancellation = $authorization->getCancellation('s-cnl-2', true);
        $this->assertInstanceOf(Cancellation::class, $cancellation);
        $this->assertEquals(11.111, $cancellation->getAmount());
        $this->assertCount(2, $authorize->getCancellations());
    }

    /**
     * Verify that handleResponse will throw an exception if the authorization to a reversal does not exist.
     *
     * @test
     */
    public function handleResponseShouldThrowExceptionIfAnAuthorizeToAReversalDoesNotExist(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');

        $cancellation = new stdClass();
        $cancellation->url = 'https://api-url.test/payments/MyPaymentId/authorize/s-aut-1/cancel/s-cnl-2';
        $cancellation->amount = '11.111';
        $cancellation->type = 'cancel-authorize';

        $response = new stdClass();
        $response->transactions = [$cancellation];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The initial transaction object (Authorize or Charge) can not be found.');
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse updates existing refunds from response.
     *
     * @test
     */
    public function handleResponseShouldUpdateRefundsFromResponseIfItExists(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');
        $charge = (new Charge(23.55, 'EUR'))->setId('s-chg-1');
        $payment->addCharge($charge);
        $refund1 = (new Cancellation(1.98))->setId('s-cnl-1');
        $refund2 = (new Cancellation(2.98))->setId('s-cnl-2');
        $this->assertEquals(2.98, $refund2->getAmount());
        $charge->addCancellation($refund1)->addCancellation($refund2);

        $cancellation = new stdClass();
        $cancellation->url = 'https://api-url.test/payments/MyPaymentId/charge/s-chg-1/cancel/s-cnl-2';
        $cancellation->amount = '11.111';
        $cancellation->type = 'cancel-charge';

        $response = new stdClass();
        $response->transactions = [$cancellation];
        $payment->handleResponse($response);

        $fetchedCharge = $payment->getCharge('s-chg-1', true);
        $cancellation = $fetchedCharge->getCancellation('s-cnl-2', true);
        $this->assertInstanceOf(Cancellation::class, $cancellation);
        $this->assertSame($refund2, $cancellation);
        $this->assertEquals(11.111, $cancellation->getAmount());
    }

    /**
     * Verify handleResponse adds non existing refund from response.
     *
     * @test
     */
    public function handleResponseShouldAddRefundFromResponseIfItDoesNotExists(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');
        $charge = (new Charge(23.55, 'EUR'))->setId('s-chg-1');
        $payment->addCharge($charge);
        $reversal1 = (new Cancellation(1.98))->setId('s-cnl-1');
        $charge->addCancellation($reversal1);
        $this->assertNull($charge->getCancellation('s-cnl-2'));
        $this->assertCount(1, $charge->getCancellations());


        $cancellation = new stdClass();
        $cancellation->url = 'https://api-url.test/payments/MyPaymentId/charge/s-chg-1/cancel/s-cnl-2';
        $cancellation->amount = '11.111';
        $cancellation->type = 'cancel-charge';

        $response = new stdClass();
        $response->transactions = [$cancellation];
        $payment->handleResponse($response);

        $fetchedCharge = $payment->getCharge('s-chg-1', true);
        $cancellation = $fetchedCharge->getCancellation('s-cnl-2', true);
        $this->assertInstanceOf(Cancellation::class, $cancellation);
        $this->assertEquals(11.111, $cancellation->getAmount());
        $this->assertCount(2, $charge->getCancellations());
    }

    /**
     * Verify that handleResponse will throw an exception if the charge to a refund does not exist.
     *
     * @test
     */
    public function handleResponseShouldThrowExceptionIfAChargeToARefundDoesNotExist(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');

        $cancellation = new stdClass();
        $cancellation->url = 'https://api-url.test/payments/MyPaymentId/charge/s-chg-1/cancel/s-cnl-2';
        $cancellation->amount = '11.111';
        $cancellation->type = 'cancel-charge';

        $response = new stdClass();
        $response->transactions = [$cancellation];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The Charge object can not be found.');
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse updates existing shipment from response.
     *
     * @test
     */
    public function handleResponseShouldUpdateShipmentFromResponseIfItExists(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');
        $shipment = (new Shipment())->setAmount('1.23')->setId('s-shp-1');
        $this->assertEquals('1.23', $shipment->getAmount());
        $payment->addShipment($shipment);

        $shipmentResponse = new stdClass();
        $shipmentResponse->url = 'https://api-url.test/payments/MyPaymentId/shipment/s-shp-1';
        $shipmentResponse->amount = '11.111';
        $shipmentResponse->type = 'shipment';

        $response = new stdClass();
        $response->transactions = [$shipmentResponse];
        $payment->handleResponse($response);

        $fetchedShipment = $payment->getShipment('s-shp-1', true);
        $this->assertInstanceOf(Shipment::class, $fetchedShipment);
        $this->assertSame($shipment, $fetchedShipment);
        $this->assertEquals(11.111, $fetchedShipment->getAmount());
    }

    /**
     * Verify handleResponse adds non existing shipment from response.
     *
     * @test
     */
    public function handleResponseShouldAddShipmentFromResponseIfItDoesNotExists(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');
        $this->assertNull($payment->getShipment('s-shp-1'));
        $this->assertCount(0, $payment->getShipments());

        $shipment = new stdClass();
        $shipment->url = 'https://api-url.test/payments/MyPaymentId/shipment/s-shp-1';
        $shipment->amount = '11.111';
        $shipment->type = 'shipment';

        $response = new stdClass();
        $response->transactions = [$shipment];
        $payment->handleResponse($response);

        $fetchedShipment = $payment->getShipment('s-shp-1', true);
        $this->assertInstanceOf(Shipment::class, $fetchedShipment);
        $this->assertEquals(11.111, $fetchedShipment->getAmount());
        $this->assertCount(1, $payment->getShipments());
    }

    /**
     * Verify handleResponse updates existing payout from response.
     *
     * @test
     */
    public function handleResponseShouldUpdatePayoutFromResponseIfItExists(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');
        $payout = (new Payout())->setAmount('1.23')->setId('s-out-1');
        $this->assertEquals('1.23', $payout->getAmount());
        $payment->setPayout($payout);

        $payoutResponse = new stdClass();
        $payoutResponse->url = 'https://api-url.test/payments/MyPaymentId/payouts/s-out-1';
        $payoutResponse->amount = '11.111';
        $payoutResponse->type = 'payout';

        $response = new stdClass();
        $response->transactions = [$payoutResponse];
        $payment->handleResponse($response);

        $fetchedPayout = $payment->getPayout(true);
        $this->assertInstanceOf(Payout::class, $fetchedPayout);
        $this->assertSame($payout, $fetchedPayout);
        $this->assertEquals(11.111, $fetchedPayout->getAmount());
    }

    /**
     * Verify handleResponse adds non existing refund from response.
     *
     * @test
     */
    public function handleResponseShouldAddPayoutFromResponseIfItDoesNotExists(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment = (new Payment())->setParentResource($unzer)->setId('MyPaymentId');
        $this->assertNull($payment->getPayout('s-out-1'));

        $payoutResponse = new stdClass();
        $payoutResponse->url = 'https://api-url.test/payments/MyPaymentId/payouts/s-out-1';
        $payoutResponse->amount = '11.111';
        $payoutResponse->type = 'payout';

        $response = new stdClass();
        $response->transactions = [$payoutResponse];
        $payment->handleResponse($response);

        $fetchedPayout = $payment->getPayout(true);
        $this->assertInstanceOf(Payout::class, $fetchedPayout);
        $this->assertEquals(11.111, $fetchedPayout->getAmount());
    }

    //</editor-fold>

    /**
     * Verify charge will call chargePayment on Unzer object.
     *
     * @test
     */
    public function chargeMethodShouldPropagateToUnzerChargePaymentMethod(): void
    {
        $payment = new Payment();

        /** @var Unzer|MockObject $unzerMock */
        $unzerMock = $this->getMockBuilder(Unzer::class)->disableOriginalConstructor()->setMethods(['chargePayment'])->getMock();
        $unzerMock->expects($this->exactly(3))->method('chargePayment')
            ->withConsecutive(
                [$payment, null, null],
                [$payment, 1.1, null],
                [$payment, 2.2]
            )->willReturn(new Charge());
        $payment->setParentResource($unzerMock);

        $payment->charge();
        $payment->charge(1.1);
        $payment->charge(2.2);
    }

    /**
     * Verify ship will call ship method on Unzer object.
     *
     * @test
     */
    public function shipMethodShouldPropagateToUnzerChargePaymentMethod(): void
    {
        $payment = new Payment();

        /** @var Unzer|MockObject $unzerMock */
        $unzerMock = $this->getMockBuilder(Unzer::class)->disableOriginalConstructor()->setMethods(['ship'])->getMock();
        $unzerMock->expects($this->once())->method('ship')->willReturn(new Shipment());

        $payment->setParentResource($unzerMock);
        $payment->ship();
    }

    /**
     * Verify setMetadata will set parent resource and call create with metadata object.
     *
     * @test
     */
    public function setMetaDataShouldSetParentResourceAndCreateMetaDataObject(): void
    {
        $metadata = (new Metadata())->addMetadata('myData', 'myValue');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setMethods(['createResource'])->disableOriginalConstructor()->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->once())->method('createResource')->with($metadata);

        $unzer = (new Unzer('s-priv-1234'))->setResourceService($resourceSrvMock);
        $payment = new Payment($unzer);

        try {
            $metadata->getParentResource();
            $this->assertTrue(false, 'This exception should have been thrown!');
        } catch (RuntimeException $e) {
            $this->assertInstanceOf(RuntimeException::class, $e);
            $this->assertEquals('Parent resource reference is not set!', $e->getMessage());
        }

        $payment->setMetadata($metadata);
        $this->assertSame($unzer, $metadata->getParentResource());
    }

    /**
     * Verify setMetadata will not set the metadata property if it is not of type metadata.
     *
     * @test
     */
    public function metadataMustBeOfTypeMetadata(): void
    {
        $metadata = new Metadata();

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setMethods(['createResource'])->disableOriginalConstructor()->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->once())->method('createResource')->with($metadata);
        $unzer = (new Unzer('s-priv-1234'))->setResourceService($resourceSrvMock);

        // when
        $payment = new Payment($unzer);

        // then
        $this->assertNull($payment->getMetadata());

        // when
        /** @noinspection PhpParamsInspection */
        $payment->setMetadata(null);

        // then
        $this->assertNull($payment->getMetadata());

        // when
        $payment->setMetadata($metadata);

        // then
        $this->assertSame($metadata, $payment->getMetadata());
    }

    /**
     * Verify set Basket will call create if the given basket object does not exist yet.
     *
     * @test
     */
    public function setBasketShouldCallCreateIfTheGivenBasketObjectDoesNotExistYet(): void
    {
        $unzer = new Unzer('s-priv-123');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setConstructorArgs([$unzer])->setMethods(['createResource'])->getMock();
        $unzer->setResourceService($resourceSrvMock);

        $basket = new Basket();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->once())->method('createResource')->with(
            $this->callback(
                static function ($object) use ($basket, $unzer) {
                    /** @var Basket $object */
                    return $object === $basket && $object->getParentResource() === $unzer;
                }
            )
        );

        $payment = new Payment($unzer);
        $payment->setBasket($basket);
    }

    /**
     * Verify setBasket won't call resource service when the basket is null.
     *
     * @test
     */
    public function setBasketWontCallResourceServiceWhenBasketIsNull(): void
    {
        $unzer = new Unzer('s-priv-123');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setConstructorArgs([$unzer])->setMethods(['createResource'])->getMock();
        $resourceSrvMock->expects($this->once())->method('createResource');
        $unzer->setResourceService($resourceSrvMock);

        // set basket first to prove the setter works both times
        $basket = new Basket();
        $payment = new Payment($unzer);
        $payment->setBasket($basket);
        $this->assertSame($basket, $payment->getBasket());

        $payment->setBasket(null);
        $this->assertNull($payment->getBasket());
    }

    /**
     * Verify updateResponseResources will fetch the basketId in response if it is set.
     *
     * @test
     */
    public function updateResponseResourcesShouldFetchBasketIdIfItIsSetInResponse(): void
    {
        /** @var Unzer|MockObject $unzerMock */
        $unzerMock = $this->getMockBuilder(Unzer::class)->disableOriginalConstructor()->setMethods(['fetchBasket'])->getMock();

        $basket = new Basket();
        /** @noinspection PhpParamsInspection */
        $unzerMock->expects($this->once())->method('fetchBasket')->with('myResourcesBasketId')->willReturn($basket);

        $payment  = new Payment($unzerMock);
        $response = new stdClass();
        $payment->handleResponse($response);
        $this->assertNull($payment->getBasket());

        $response->resources = new stdClass();
        $response->resources->basketId = 'myResourcesBasketId';
        $payment->handleResponse($response);
    }

    /**
     * Verify a payment is fetched by orderId if the id is not set.
     *
     * @test
     */
    public function paymentShouldBeFetchedByOrderIdIfIdIsNotSet(): void
    {
        $orderId     = str_replace(' ', '', microtime());
        $payment     = (new Payment())->setOrderId($orderId)->setParentResource(new Unzer('s-priv-123'));
        $lastElement = explode('/', rtrim($payment->getUri(), '/'));
        $this->assertEquals($orderId, end($lastElement));
    }

    /**
     * Verify getInitialTransaction method returns the initial transaction.
     * Autofetch is disabled due to missing transactionIds.
     *
     * @test
     *
     * @dataProvider initialTransactionDP
     *
     * @param AbstractTransactionType $expected
     * @param Payment                 $payment
     */
    public function initialTransactionShouldBeAuthIfExistsElseFirstCharge($expected, Payment $payment): void
    {
        $this->assertSame($expected, $payment->getInitialTransaction());
    }

    //<editor-fold desc="Data Providers">

    /**
     * Provides the different payment states.
     *
     * @return array
     */
    public function stateDataProvider(): array
    {
        return [
            PaymentState::STATE_NAME_PENDING        => [PaymentState::STATE_PENDING],
            PaymentState::STATE_NAME_COMPLETED      => [PaymentState::STATE_COMPLETED],
            PaymentState::STATE_NAME_CANCELED       => [PaymentState::STATE_CANCELED],
            PaymentState::STATE_NAME_PARTLY         => [PaymentState::STATE_PARTLY],
            PaymentState::STATE_NAME_PAYMENT_REVIEW => [PaymentState::STATE_PAYMENT_REVIEW],
            PaymentState::STATE_NAME_CHARGEBACK     => [PaymentState::STATE_CHARGEBACK]
        ];
    }

    /**
     * Data provider to initialTransactionShouldBeAuthIfExistsElseFirstCharge
     */
    public function initialTransactionDP(): array
    {
        $authorize = new Authorization();
        $charge = new Charge();

        return [
            'charge' => [$charge, (new Payment($this->getUnzerObject()))->addCharge($charge)],
            'authorize' => [$authorize, (new Payment($this->getUnzerObject()))->setAuthorization($authorize)],
            'authorize and charge' => [$authorize, (new Payment($this->getUnzerObject()))->addCharge($charge)->setAuthorization($authorize)]
        ];
    }

    //</editor-fold>
}
