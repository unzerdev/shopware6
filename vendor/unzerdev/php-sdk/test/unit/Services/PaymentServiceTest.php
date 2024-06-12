<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the payment service.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Services;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Constants\TransactionTypes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Unzer;
use UnzerSDK\Interfaces\CancelServiceInterface;
use UnzerSDK\Interfaces\ResourceServiceInterface;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\InstalmentPlans;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Resources\PaymentTypes\Paypage;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;
use UnzerSDK\Resources\PaymentTypes\Sofort;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Payout;
use UnzerSDK\Resources\TransactionTypes\Shipment;
use UnzerSDK\Services\CancelService;
use UnzerSDK\Services\PaymentService;
use UnzerSDK\Services\ResourceService;
use UnzerSDK\test\BasePaymentTest;
use PHPUnit\Framework\MockObject\MockObject;

use function in_array;

class PaymentServiceTest extends BasePaymentTest
{
    //<editor-fold desc="General">

    /**
     * Verify setters and getters work properly.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkProperly(): void
    {
        $unzer          = new Unzer('s-priv-123');
        /** @var PaymentService $paymentService */
        $paymentService = $unzer->getPaymentService();
        $this->assertSame($unzer, $paymentService->getUnzer());
        $this->assertSame($unzer->getResourceService(), $paymentService->getResourceService());

        $unzer2       = new Unzer('s-priv-1234');
        $paymentService->setUnzer($unzer2);
        $this->assertSame($unzer2, $paymentService->getUnzer());
    }

    //</editor-fold>

    //<editor-fold desc="Authorize">

    /**
     * Verify authorize calls create for a new authorization using the passed values.
     *
     * @test
     *
     * @param $card3ds
     *
     * @dataProvider card3dsDataProvider
     */
    public function authorizeShouldCreateNewAuthorizationAndPayment($card3ds): void
    {
        $customer  = (new Customer())->setId('myCustomerId');
        $metadata  = (new Metadata())->setId('myMetadataId');
        $basket    = (new Basket())->setId('myBasketId');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['createResource'])->getMock();
        $paymentSrv  = (new Unzer('s-priv-123'))->setResourceService($resourceSrvMock)->getPaymentService();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->once())->method('createResource')
            ->with($this->callback(static function ($authorize) use ($customer, $metadata, $basket, $card3ds) {
                /** @var Authorization $authorize */
                $newPayment = $authorize->getPayment();
                return $authorize instanceof Authorization &&
                    $authorize->getAmount() === 1.234 &&
                    $authorize->getCurrency() === 'myCurrency' &&
                    $authorize->getOrderId() === 'myId' &&
                    $authorize->getReturnUrl() === 'myUrl' &&
                    $authorize->isCard3ds() === $card3ds &&
                    $newPayment instanceof Payment &&
                    $newPayment->getMetadata() === $metadata &&
                    $newPayment->getCustomer() === $customer &&
                    $newPayment->getBasket() === $basket &&
                    $newPayment->getAuthorization() === $authorize;
            }));

        $type = (new PayPal())->setId('typeId');
        $paymentSrv->authorize(1.234, 'myCurrency', $type, 'myUrl', $customer, 'myId', $metadata, $basket, $card3ds);
    }

    //</editor-fold>

    //<editor-fold desc="Charge">

    /**
     * Verify charge method calls create with a charge object on resource service.
     *
     * @test
     *
     * @param $card3ds
     *
     * @dataProvider card3dsDataProvider
     */
    public function chargeShouldCreateNewPaymentAndCharge($card3ds): void
    {
        $customer    = (new Customer())->setId('myCustomerId');
        $unzer   = new Unzer('s-priv-123');
        $paymentType = (new Sofort())->setId('myPaymentTypeId');
        $metadata    = (new Metadata())->setId('myMetadataId');
        $basket      = (new Basket())->setId('myBasketId');

        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['createResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->once())->method('createResource')
            ->with($this->callback(static function ($charge) use ($customer, $paymentType, $basket, $card3ds) {
                /** @var Charge $charge */
                $newPayment = $charge->getPayment();
                return $charge instanceof Charge &&
                    $charge->getAmount() === 1.234 &&
                    $charge->getCurrency() === 'myCurrency' &&
                    $charge->getOrderId() === 'myId' &&
                    $charge->getReturnUrl() === 'myUrl' &&
                    $charge->isCard3ds() === $card3ds &&
                    $newPayment instanceof Payment &&
                    $newPayment->getCustomer() === $customer &&
                    $newPayment->getPaymentType() === $paymentType &&
                    $newPayment->getBasket() === $basket &&
                    in_array($charge, $newPayment->getCharges(), true);
            }));

        /** @var ResourceService $resourceSrvMock */
        $paymentSrv     = $unzer->setResourceService($resourceSrvMock)->getPaymentService();
        $returnedCharge = $paymentSrv->charge(1.234, 'myCurrency', $paymentType, 'myUrl', $customer, 'myId', $metadata, $basket, $card3ds);
        $this->assertSame($paymentType, $returnedCharge->getPayment()->getPaymentType());
    }

    /**
     * Verify chargeAuthorization calls chargePayment with the given payment object.
     *
     * @test
     */
    public function chargeAuthorizationShouldCallChargePaymentWithTheGivenPaymentObject(): void
    {
        $paymentObject  = (new Payment())->setId('myPaymentId');
        /** @var PaymentService|MockObject $paymentSrvMock */
        $paymentSrvMock = $this->getMockBuilder(PaymentService::class)->setMethods(['chargePayment'])->disableOriginalConstructor()->getMock();
        $paymentSrvMock->expects($this->exactly(2))->method('chargePayment')->withConsecutive([$paymentObject, null], [$paymentObject, 1.234]);

        $paymentSrvMock->setUnzer((new Unzer('s-priv-123'))->setPaymentService($paymentSrvMock));
        $paymentSrvMock->chargeAuthorization($paymentObject);
        $paymentSrvMock->chargeAuthorization($paymentObject, 1.234);
    }

    /**
     * @deprecated
     * Verify chargeAuthorization calls fetchPayment if the payment object is passed as id string.
     *
     * @test
     */
    public function chargeAuthorizationShouldCallChargePaymentMethod(): void
    {
        /** @var ResourceServiceInterface|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setMethods(['fetchPayment'])->disableOriginalConstructor()->getMock();
        /** @var PaymentService|MockObject $paymentSrvMock */
        $paymentSrvMock = $this->getMockBuilder(PaymentService::class)->setMethods(['chargePayment'])->disableOriginalConstructor()->getMock();
        $paymentSrvMock->expects($this->once())->method('chargePayment')->withAnyParameters();

        $paymentSrvMock->chargeAuthorization('myPaymentId');
    }

    /**
     * @deprecated
     * Verify chargePayment calls fetchPayment if the payment object is passed as id string.
     *
     * @test
     */
    public function chargePaymentShouldCallerformChargeOnPaymentMethod(): void
    {
        /** @var ResourceServiceInterface|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setMethods(['fetchPayment'])->disableOriginalConstructor()->getMock();
        /** @var PaymentService|MockObject $paymentSrvMock */
        $paymentSrvMock = $this->getMockBuilder(PaymentService::class)->setMethods(['performChargeOnPayment'])->disableOriginalConstructor()->getMock();
        $paymentSrvMock->expects($this->once())->method('performChargeOnPayment')->withAnyParameters();

        $paymentSrvMock->chargePayment('myPaymentId');
    }

    /**
     * Verify performChargeOnPayment calls fetchPayment if the payment object is passed as id string.
     *
     * @test
     */
    public function performChargeOnPaymentShouldCallFetchPaymentIfThePaymentIsPassedAsIdString(): void
    {
        /** @var ResourceServiceInterface|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setMethods(['fetchPayment', 'createResource'])->disableOriginalConstructor()->getMock();
        $resourceSrvMock->expects($this->once())->method('fetchPayment')->willReturn(new Payment());
        $resourceSrvMock->expects($this->once())->method('createResource')->willReturn(new Charge());
        /** @var PaymentService|MockObject $paymentSrvMock */
        $paymentSrvMock = $this->getMockBuilder(PaymentService::class)->setMethods(['getResourceService'])->disableOriginalConstructor()->getMock();
        $paymentSrvMock->expects(self::exactly(2))->method('getResourceService')->willReturn($resourceSrvMock);

        $paymentSrvMock->performChargeOnPayment('myPaymentId', new Charge());
    }

    /**
     * Verify chargePayment will create a charge object and call create on ResourceService with it.
     *
     * @test
     */
    public function chargePaymentShouldCallCreateOnResourceServiceWithNewCharge(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment   = (new Payment())->setParentResource($unzer)->setId('myPaymentId');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['createResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->once())->method('createResource')
            ->with($this->callback(static function ($charge) use ($payment) {
                /** @var Charge $charge */
                $newPayment = $charge->getPayment();
                return $charge instanceof Charge &&
                    $charge->getAmount() === 1.234 &&
                    $charge->getOrderId() === null &&
                    $charge->getInvoiceId() === null &&
                    $newPayment instanceof Payment &&
                    $newPayment === $payment &&
                    in_array($charge, $newPayment->getCharges(), true);
            }));

        $paymentSrv     = $unzer->setResourceService($resourceSrvMock)->getPaymentService();
        $returnedCharge = $paymentSrv->chargePayment($payment, 1.234);
        $this->assertEquals([$returnedCharge], $payment->getCharges());
    }

    /**
     * Verify chargePayment will set Ids if they are defined.
     *
     * @test
     */
    public function chargePaymentShouldSetArgumentsInNewChargeObject(): void
    {
        $unzer = new Unzer('s-priv-123');
        $payment   = (new Payment())->setParentResource($unzer)->setId('myPaymentId');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['createResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->once())->method('createResource')
            ->with($this->callback(static function ($charge) use ($payment) {
                /** @var Charge $charge */
                $newPayment = $charge->getPayment();
                return $charge instanceof Charge &&
                    $charge->getAmount() === 1.234 &&
                    $charge->getOrderId() === 'orderId' &&
                    $charge->getInvoiceId() === 'invoiceId' &&
                    $newPayment instanceof Payment &&
                    $newPayment === $payment &&
                    in_array($charge, $newPayment->getCharges(), true);
            }));

        $paymentSrv     = $unzer->setResourceService($resourceSrvMock)->getPaymentService();
        $returnedCharge = $paymentSrv->chargePayment($payment, 1.234, 'orderId', 'invoiceId');
        $this->assertEquals([$returnedCharge], $payment->getCharges());
    }

    //</editor-fold>

    //<editor-fold desc="Cancel">

    /**
     * Verify cancelAuthorization will create a cancellation object and call create on ResourceService with it.
     *
     * @test
     */
    public function cancelAuthorizationShouldCallCreateOnResourceServiceWithNewCancellation(): void
    {
        $unzer     = new Unzer('s-priv-123');
        $payment       = (new Payment())->setParentResource($unzer)->setId('myPaymentId');
        $authorization = (new Authorization())->setPayment($payment)->setId('s-aut-1');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['createResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->once())->method('createResource')
            ->with($this->callback(static function ($cancellation) use ($payment) {
                /** @var Cancellation $cancellation */
                $newPayment = $cancellation->getPayment();
                return $cancellation instanceof Cancellation &&
                    $cancellation->getAmount() === 12.122 &&
                    $newPayment instanceof Payment &&
                    $newPayment === $payment;
            }))->will($this->returnArgument(0));

        $cancelSrv            = $unzer->setResourceService($resourceSrvMock)->getCancelService();
        $returnedCancellation = $cancelSrv->cancelAuthorization($authorization, 12.122);

        $this->assertSame(12.122, $returnedCancellation->getAmount());
        $this->assertSame($payment, $returnedCancellation->getPayment());
    }

    /**
     * Verify cancelAuthorization will create a cancellation object and call create on ResourceService with it.
     *
     * @test
     */
    public function cancelAuthorizationShouldNotAddCancellationIfCancellationFails(): void
    {
        $unzer     = new Unzer('s-priv-123');
        $payment       = (new Payment())->setParentResource($unzer)->setId('myPaymentId');
        $authorization = (new Authorization())->setPayment($payment)->setId('s-aut-1');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['createResource'])->getMock();
        $cancellationException       = new UnzerApiException(
            'Cancellation failed',
            'something went wrong',
            ApiResponseCodes::API_ERROR_ALREADY_CANCELLED
        );
        $resourceSrvMock->expects($this->once())->method('createResource')->willThrowException($cancellationException);

        $cancelSrv = $unzer->setResourceService($resourceSrvMock)->getCancelService();
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_ALREADY_CANCELLED);
        $cancelSrv->cancelAuthorization($authorization, 12.122);
        $this->assertCount(0, $authorization->getCancellations());
    }

    /**
     * Verify cancelAuthorizationByPayment will propagate to cancelAuthorization method.
     *
     * @test
     */
    public function cancelAuthorizationByPaymentShouldCallCancelAuthorization(): void
    {
        $authorization = (new Authorization())->setId('s-aut-1');
        $unzer = new Unzer('s-priv-1234');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setMethods(['fetchAuthorization'])->disableOriginalConstructor()->getMock();
        $resourceSrvMock->expects($this->exactly(2))->method('fetchAuthorization')->willReturn($authorization);
        /** @var CancelService|MockObject $cancelSrvMock */
        $cancelSrvMock = $this->getMockBuilder(CancelService::class)->setMethods(['cancelAuthorization'])->disableOriginalConstructor()->getMock();
        $cancelSrvMock->expects($this->exactly(2))->method('cancelAuthorization')->withConsecutive([$authorization, null], [$authorization, 1.123]);
        $unzer->setResourceService($resourceSrvMock)->setCancelService($cancelSrvMock);

        $cancelSrvMock->cancelAuthorizationByPayment(new Payment());
        $cancelSrvMock->cancelAuthorizationByPayment(new Payment(), 1.123);
    }

    /**
     * Verify cancelChargeById fetches Charge and propagates to cancelCharge method.
     *
     * @test
     */
    public function cancelChargeByIdShouldFetchChargeAndPropagateToCancelCharge(): void
    {
        $payment = (new Payment())->setId('myPaymentId');
        $charge  = new Charge();

        /** @var ResourceServiceInterface|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setMethods(['fetchChargeById'])->disableOriginalConstructor()->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->exactly(2))->method('fetchChargeById')->with($payment, 's-chg-1')->willReturn($charge);

        /** @var CancelServiceInterface|MockObject $cancelSrvMock */
        $cancelSrvMock = $this->getMockBuilder(CancelService::class)->setMethods(['cancelCharge', 'getResourceService'])->disableOriginalConstructor()->getMock();
        $cancelSrvMock->expects($this->exactly(2))->method('cancelCharge')->withConsecutive([$charge], [$charge, 10.11]);
        $cancelSrvMock->expects($this->exactly(2))->method('getResourceService')->willReturn($resourceSrvMock);

        $cancelSrvMock->cancelChargeById($payment, 's-chg-1');
        $cancelSrvMock->cancelChargeById($payment, 's-chg-1', 10.11);
    }

    /**
     * Verify cancelCharge creates new Cancellation and calls create on resourceService with it.
     *
     * @test
     */
    public function cancelChargeShouldCreateCancellationAndCallsCreate(): void
    {
        $unzer = new Unzer('s-priv-1234');
        $cancelSrv = $unzer->getCancelService();
        $payment   = (new Payment())->setParentResource($unzer);
        $charge    = (new Charge())->setPayment($payment);

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setMethods(['createResource'])->disableOriginalConstructor()->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->once())->method('createResource')
            ->with($this->callback(static function ($cancellation) use ($payment, $charge) {
                return $cancellation instanceof Cancellation &&
                    $cancellation->getAmount() === 12.22 &&
                    $cancellation->getPayment() === $payment &&
                    $cancellation->getParentResource() === $charge;
            }));
        $unzer->setResourceService($resourceSrvMock);

        $cancelSrv->cancelCharge($charge, 12.22);
    }

    //</editor-fold>

    //<editor-fold desc="Shipment">

    /**
     * Verify ship method will create a new Shipment, add it to the given payment object and call create on
     * ResourceService with the shipment object.
     *
     * @test
     */
    public function shipShouldCreateShipmentAndCallCreateOnResourceServiceWithIt(): void
    {
        $unzer  = new Unzer('s-priv-1234');
        $payment    = new Payment();

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setMethods(['createResource'])->disableOriginalConstructor()->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->exactly(2))->method('createResource')
            ->with($this->callback(static function ($shipment) use ($payment) {
                return $shipment instanceof Shipment &&
                    $shipment->getPayment() === $payment &&
                    $shipment->getParentResource() === $payment;
            }));
        $unzer->setResourceService($resourceSrvMock);

        $unzer->getPaymentService()->ship($payment);
        $this->assertCount(1, $payment->getShipments());

        $unzer->getPaymentService()->ship($payment);
        $this->assertCount(2, $payment->getShipments());
    }

    //</editor-fold>

    //<editor-fold desc="Payout">

    /**
     * Verify payout method calls payout with payment.
     *
     * @test
     */
    public function payoutShouldCreatePaymentAndCallPayoutWithPayment(): void
    {
        $paymentType = (new SepaDirectDebit('1234'))->setId('typeId');
        $customer    = (new Customer())->setId('customerId');
        $metadata    = (new Metadata())->setId('metadataId');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['createResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects(self::once())->method('createResource')
            ->with(self::callback(static function ($payout) use ($customer, $metadata) {
                return $payout instanceof Payout &&
                    $payout->getAmount() === 1.23 &&
                    $payout->getCurrency() === 'testCurrency' &&
                    $payout->getPayment() instanceof Payment &&
                    $payout->getReturnUrl() === 'http://return.url' &&
                    $customer === $payout->getPayment()->getCustomer() &&
                    $metadata === $payout->getPayment()->getMetadata();
            }));
        $unzer = (new Unzer('s-priv-123'))->setResourceService($resourceSrvMock);
        $unzer->getPaymentService()->payout(1.23, 'testCurrency', $paymentType, 'http://return.url', $customer, 'OrderId', $metadata);
    }

    /**
     * Verify payoutWithPayment calls create for a new payout using the passed values.
     *
     * @test
     */
    public function payoutShouldCreateNewPayout(): void
    {
        // we provide some fake resources with ids to avoid them to be automatically created
        $customer  = (new Customer())->setId('id-1');
        $basket    = (new Basket())->setId('id-2');
        $metadata  = (new Metadata())->setId('id-3');
        $unzer = new Unzer('s-priv-123');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['createResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->once())->method('createResource')
            ->with($this->callback(static function ($payout) use ($customer, $basket, $metadata) {
                /** @var Payout $payout */
                $newPayment = $payout->getPayment();
                return $payout instanceof Payout &&
                    $payout->getAmount() === 1.234 &&
                    $payout->getCurrency() === 'EUR' &&
                    $payout->getOrderId() === 'id-4' &&
                    $payout->getReturnUrl() === 'url' &&
                    $newPayment instanceof Payment &&
                    $newPayment->getCustomer() === $customer &&
                    $newPayment->getMetadata() === $metadata &
                    $newPayment->getBasket() === $basket &&
                    $newPayment->getPayout() === $payout;
            }));
        $paymentSrv     = $unzer->setResourceService($resourceSrvMock)->getPaymentService();
        $paymentType    = (new PayPal())->setId('id');
        $returnedPayout = $paymentSrv->payout(1.234, 'EUR', $paymentType, 'url', $customer, 'id-4', $metadata, $basket);
        $this->assertEquals(
            [
                'amount' => 1.234,
                'currency' => 'EUR',
                'orderId' => 'id-4',
                'returnUrl' => 'url',
                'resources' => ['basketId' => 'id-2', 'customerId' => 'id-1', 'metadataId' => 'id-3', 'typeId' => 'id']
            ],
            $returnedPayout->expose()
        );
    }

    //</editor-fold>

    //<editor-fold desc="PayPage">

    /** @noinspection PhpDocRedundantThrowsInspection */

    /**
     * Verify initPayPage creates a payment with resources and calls create with said payment.
     *
     * @test
     *
     * @dataProvider paymentShouldBeCreatedByInitPayPageDP
     *
     * @param string $action
     */
    public function paymentShouldBeCreatedByInitPayPage(string $action): void
    {
        $method = 'initPayPage' . $action;

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setMethods(['createResource'])->disableOriginalConstructor()->getMock();
        $paymentSrv = (new Unzer('s-priv-1234'))->setResourceService($resourceSrvMock)->getPaymentService();

        // when
        $paypage  = new Paypage(123.4, 'CHF', 'url');
        $basket   = (new Basket())->setId('basketId');
        $customer = (new Customer())->setId('customerId');
        $metadata = (new Metadata())->setId('metadataId');

        // should
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->once())->method('createResource')
            ->with($this->callback(static function ($paypage) use ($basket, $customer, $metadata, $action) {
                return $paypage instanceof Paypage &&
                    $paypage->getPayment() instanceof Payment &&
                    $basket === $paypage->getBasket() &&
                    $customer === $paypage->getCustomer() &&
                    $metadata === $paypage->getMetadata() &&
                    $action === $paypage->getAction();
            }));

        // when
        $paymentSrv->$method($paypage, $customer, $basket, $metadata);
    }

    //</editor-fold>

    //<editor-fold desc="Installment Secured">

    /**
     * Verify fetch hdd instalment plans.
     *
     * @test
     */
    public function fetchInstalmentPlansWillCallFetchOnResourceService(): void
    {
        $unzer = new Unzer('s-priv-1234');
        /** @var MockObject|ResourceService $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setConstructorArgs(['unzer' => $unzer])->setMethods(['fetchResource'])->getMock();
        $unzer->setResourceService($resourceSrvMock);

        $date = $this->getYesterdaysTimestamp();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->once())->method('fetchResource')
            ->with($this->callback(static function ($param) use ($date) {
                return $param instanceof InstalmentPlans &&
                    $param->getAmount() === 12.23 &&
                    $param->getCurrency() === 'EUR' &&
                    $param->getEffectiveInterest() === 4.99 &&
                    $param->getOrderDate() === $date->format('Y-m-d') &&
                    $param->getParentResource() instanceof InstallmentSecured;
            }))->willReturn(new InstalmentPlans(12.23, 'EUR', 4.99, $date));
        $unzer->getPaymentService()->fetchInstallmentPlans(12.23, 'EUR', 4.99, $date);
    }

    //</editor-fold>

    //<editor-fold desc="DataProviders">

    /**
     * @return array
     */
    public function card3dsDataProvider(): array
    {
        return [
            'default' => [null],
            'non 3ds' => [false],
            '3ds'     => [true]
        ];
    }

    /**
     * @return array
     */
    public function paymentShouldBeCreatedByInitPayPageDP(): array
    {
        return [
            TransactionTypes::CHARGE        => [TransactionTypes::CHARGE],
            TransactionTypes::AUTHORIZATION => [TransactionTypes::AUTHORIZATION]
        ];
    }

    //</editor-fold>
}
