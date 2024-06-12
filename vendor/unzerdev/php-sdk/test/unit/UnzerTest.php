<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the Unzer class.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit;

use DateTime;
use UnzerSDK\Unzer;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\Paypage;
use UnzerSDK\Resources\PaymentTypes\Sofort;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\Webhook;
use UnzerSDK\Services\CancelService;
use UnzerSDK\Services\HttpService;
use UnzerSDK\Services\PaymentService;
use UnzerSDK\Services\ResourceService;
use UnzerSDK\Services\WebhookService;
use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\test\unit\Services\DummyDebugHandler;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

class UnzerTest extends BasePaymentTest
{
    /**
     * Verify constructor works properly.
     *
     * @test
     */
    public function constructorShouldInitPropertiesProperly(): void
    {
        $unzer = new Unzer('s-priv-1234');
        $paymentService = $unzer->getPaymentService();
        $this->assertInstanceOf(PaymentService::class, $paymentService);
        $this->assertInstanceOf(WebhookService::class, $unzer->getWebhookService());
        /** @var PaymentService $paymentService */
        $this->assertSame($unzer, $paymentService->getUnzer());
        $this->assertEquals('s-priv-1234', $unzer->getKey());
        $this->assertEquals(null, $unzer->getLocale());

        $unzerSwiss = new Unzer('s-priv-1234', 'de-CH');
        $this->assertEquals('de-CH', $unzerSwiss->getLocale());

        $unzerGerman = new Unzer('s-priv-1234', 'de-DE');
        $this->assertEquals('de-DE', $unzerGerman->getLocale());
    }

    /**
     * Verify getters and setters work properly.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkProperly(): void
    {
        $unzer = new Unzer('s-priv-1234');
        $unzer->setLocale('myLocale');
        $unzer->setClientIp('myIpAddress');
        $this->assertEquals('myLocale', $unzer->getLocale());
        $this->assertEquals('myIpAddress', $unzer->getClientIp());

        try {
            $unzer->setKey('this is not a valid key');
            $this->assertTrue(false, 'This exception should have been thrown');
        } catch (RuntimeException $e) {
            $this->assertEquals('Illegal key: Use a valid private key with this SDK!', $e->getMessage());
        }

        $httpService = new HttpService();
        $this->assertNotSame($httpService, $unzer->getHttpService());
        $unzer->setHttpService($httpService);
        $this->assertSame($httpService, $unzer->getHttpService());

        $resourceSrv = new ResourceService($unzer);
        $unzer->setResourceService($resourceSrv);
        $this->assertSame($resourceSrv, $unzer->getResourceService());

        $paymentSrv = new PaymentService($unzer);
        $unzer->setPaymentService($paymentSrv);
        $this->assertSame($paymentSrv, $unzer->getPaymentService());

        $webhookSrv = new WebhookService($unzer);
        $unzer->setWebhookService($webhookSrv);
        $this->assertSame($webhookSrv, $unzer->getWebhookService());

        $this->assertFalse($unzer->isDebugMode());
        $unzer->setDebugMode(true);
        $this->assertTrue($unzer->isDebugMode());
        $unzer->setDebugMode(false);
        $this->assertFalse($unzer->isDebugMode());

        $this->assertNull($unzer->getDebugHandler());
        $dummyDebugHandler = new DummyDebugHandler();
        $unzer->setDebugHandler($dummyDebugHandler);
        $this->assertSame($dummyDebugHandler, $unzer->getDebugHandler());

        $this->assertEquals('', $unzer->getUri());
    }

    /**
     * Verify Unzer propagates resource actions to the resource service.
     *
     * @test
     *
     * @dataProvider resourceServiceDP
     *
     * @param string $unzerMethod
     * @param array  $unzerParams
     * @param string $serviceMethod
     * @param array  $serviceParams
     */
    public function unzerShouldForwardResourceActionCallsToTheResourceService(
        $unzerMethod,
        array $unzerParams,
        $serviceMethod,
        array $serviceParams
    ): void {
        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods([$serviceMethod])->getMock();
        $resourceSrvMock->expects($this->once())->method($serviceMethod)->with(...$serviceParams);
        $unzer = (new Unzer('s-priv-234'))->setResourceService($resourceSrvMock);

        $unzer->$unzerMethod(...$unzerParams);
    }

    /**
     * Verify Unzer propagates payment actions to the payment service.
     *
     * @test
     *
     * @dataProvider paymentServiceDP
     *
     * @param string $unzerMethod
     * @param array  $unzerParams
     * @param string $serviceMethod
     * @param array  $serviceParams
     */
    public function unzerShouldForwardPaymentActionCallsToThePaymentService(
        $unzerMethod,
        array $unzerParams,
        $serviceMethod,
        array $serviceParams
    ): void {
        /** @var PaymentService|MockObject $paymentSrvMock */
        $paymentSrvMock = $this->getMockBuilder(PaymentService::class)->disableOriginalConstructor()->setMethods([$serviceMethod])->getMock();
        $paymentSrvMock->expects($this->once())->method($serviceMethod)->with(...$serviceParams);
        $unzer = (new Unzer('s-priv-234'))->setPaymentService($paymentSrvMock);

        $unzer->$unzerMethod(...$unzerParams);
    }

    /**
     * Verify Unzer propagates webhook actions to the webhook service.
     *
     * @test
     *
     * @dataProvider UnzerShouldForwardWebhookActionCallsToTheWebhookServiceDP
     *
     * @param string $unzerMethod
     * @param array  $unzerParams
     * @param string $serviceMethod
     * @param array  $serviceParams
     */
    public function unzerShouldForwardWebhookActionCallsToTheWebhookService(
        $unzerMethod,
        array $unzerParams,
        $serviceMethod,
        array $serviceParams
    ): void {
        /** @var WebhookService|MockObject $webhookSrvMock */
        $webhookSrvMock = $this->getMockBuilder(WebhookService::class)->disableOriginalConstructor()->setMethods([$serviceMethod])->getMock();
        $webhookSrvMock->expects($this->once())->method($serviceMethod)->with(...$serviceParams);
        $unzer = (new Unzer('s-priv-234'))->setWebhookService($webhookSrvMock);

        $unzer->$unzerMethod(...$unzerParams);
    }

    /**
     * Verify Unzer propagates cancel actions to the cancel service.
     *
     * @test
     *
     * @dataProvider cancelServiceDP
     *
     * @param string $unzerMethod
     * @param array  $unzerParams
     * @param string $serviceMethod
     * @param array  $serviceParams
     */
    public function unzerShouldForwardCancelActionCallsToTheCancelService(
        $unzerMethod,
        array $unzerParams,
        $serviceMethod,
        array $serviceParams
    ): void {
        /** @var CancelService|MockObject $cancelSrvMock */
        $cancelSrvMock = $this->getMockBuilder(CancelService::class)->disableOriginalConstructor()->setMethods([$serviceMethod])->getMock();
        $cancelSrvMock->expects($this->once())->method($serviceMethod)->with(...$serviceParams);
        $unzer = (new Unzer('s-priv-234'))->setCancelService($cancelSrvMock);

        $unzer->$unzerMethod(...$unzerParams);
    }

    //<editor-fold desc="DataProviders">

    /**
     * Provide test data for unzerShouldForwardResourceActionCallsToTheResourceService.
     *
     * @return array
     */
    public static function resourceServiceDP(): array
    {
        $customerId     = 'customerId';
        $basketId       = 'basketId';
        $paymentId      = 'paymentId';
        $chargeId       = 'chargeId';
        $cancelId       = 'cancelId';
        $metadataId     = 'metaDataId';
        $orderId        = 'orderId';
        $paymentTypeId  = 'paymentTypeId';
        $customer       = new Customer();
        $basket         = new Basket();
        $payment        = new Payment();
        $sofort         = new Sofort();
        $card           = new Card('', '03/33');
        $auth           = new Authorization();
        $charge         = new Charge();
        $metadata       = new Metadata();

        return [
            'fetchPayment'                 => ['fetchPayment', [$payment], 'fetchPayment', [$payment]],
            'fetchPaymentByOrderId'        => ['fetchPaymentByOrderId', [$orderId], 'fetchPaymentByOrderId', [$orderId]],
            'fetchPaymentStr'              => ['fetchPayment', [$paymentId], 'fetchPayment', [$paymentId]],
            'fetchKeypair'                 => ['fetchKeypair', [], 'fetchKeypair', []],
            'createMetadata'               => ['createMetadata', [$metadata], 'createMetadata', [$metadata]],
            'fetchMetadata'                => ['fetchMetadata', [$metadata], 'fetchMetadata', [$metadata]],
            'fetchMetadataStr'             => ['fetchMetadata', [$metadataId], 'fetchMetadata', [$metadataId]],
            'createPaymentType'            => ['createPaymentType', [$sofort], 'createPaymentType', [$sofort]],
            'fetchPaymentType'             => ['fetchPaymentType', [$paymentTypeId], 'fetchPaymentType', [$paymentTypeId]],
            'createCustomer'               => ['createCustomer', [$customer], 'createCustomer', [$customer]],
            'createOrUpdateCustomer'       => ['createOrUpdateCustomer', [$customer], 'createOrUpdateCustomer', [$customer]],
            'fetchCustomer'                => ['fetchCustomer', [$customer], 'fetchCustomer', [$customer]],
            'fetchCustomerByExtCustomerId' => ['fetchCustomerByExtCustomerId', [$customerId], 'fetchCustomerByExtCustomerId', [$customerId]],
            'fetchCustomerStr'             => ['fetchCustomer', [$customerId], 'fetchCustomer', [$customerId]],
            'updateCustomer'               => ['updateCustomer', [$customer], 'updateCustomer', [$customer]],
            'deleteCustomer'               => ['deleteCustomer', [$customer], 'deleteCustomer', [$customer]],
            'deleteCustomerStr'            => ['deleteCustomer', [$customerId], 'deleteCustomer', [$customerId]],
            'createBasket'                 => ['createBasket', [$basket], 'createBasket', [$basket]],
            'fetchBasket'                  => ['fetchBasket', [$basket], 'fetchBasket', [$basket]],
            'fetchBasketStr'               => ['fetchBasket', [$basketId], 'fetchBasket', [$basketId]],
            'updateBasket'                 => ['updateBasket', [$basket], 'updateBasket', [$basket]],
            'fetchAuthorization'           => ['fetchAuthorization', [$payment], 'fetchAuthorization', [$payment]],
            'fetchAuthorizationStr'        => ['fetchAuthorization', [$paymentId], 'fetchAuthorization', [$paymentId]],
            'fetchChargeById'              => ['fetchChargeById', [$paymentId, $chargeId], 'fetchChargeById', [$paymentId, $chargeId]],
            'fetchCharge'                  => ['fetchCharge', [$charge], 'fetchCharge', [$charge]],
            'fetchReversalByAuthorization' => ['fetchReversalByAuthorization', [$auth, $cancelId], 'fetchReversalByAuthorization', [$auth, $cancelId]],
            'fetchReversal'                => ['fetchReversal', [$payment, $cancelId], 'fetchReversal', [$payment, $cancelId]],
            'fetchReversalStr'             => ['fetchReversal', [$paymentId, $cancelId], 'fetchReversal', [$paymentId, $cancelId]],
            'fetchRefundById'              => ['fetchRefundById', [$payment, $chargeId, $cancelId], 'fetchRefundById', [$payment, $chargeId, $cancelId]],
            'fetchRefundByIdStr'           => ['fetchRefundById', [$paymentId, $chargeId, $cancelId], 'fetchRefundById', [$paymentId, $chargeId, $cancelId]],
            'fetchRefund'                  => ['fetchRefund', [$charge, $cancelId], 'fetchRefund', [$charge, $cancelId]],
            'fetchShipment'                => ['fetchShipment', [$payment, 'shipId'], 'fetchShipment', [$payment, 'shipId']],
            'activateRecurring'            => ['activateRecurringPayment', [$card, 'returnUrl'], 'activateRecurringPayment', [$card, 'returnUrl']],
            'activateRecurringWithId'      => ['activateRecurringPayment', [$paymentTypeId, 'returnUrl'], 'activateRecurringPayment', [$paymentTypeId, 'returnUrl']],
            'fetchPayout'                  => ['fetchPayout', [$payment], 'fetchPayout', [$payment]],
            'updatePaymentType'            => ['updatePaymentType', [$card], 'updatePaymentType', [$card]]
        ];
    }

    /**
     * Provide test data for unzerShouldForwardPaymentActionCallsToThePaymentService.
     *
     * @return array
     */
    public static function paymentServiceDP(): array
    {
        $url           = 'https://dev.unzer.com';
        $orderId       = 'orderId';
        $paymentTypeId = 'paymentTypeId';
        $customerId    = 'customerId';
        $paymentId     = 'paymentId';
        $customer      = new Customer();
        $sofort        = new Sofort();
        $metadata      = new Metadata();
        $payment       = new Payment();
        $paypage       = new Paypage(123.1234, 'EUR', 'url');
        $basket        = new Basket();
        $today         = new DateTime();

        return [
            'auth'                   => ['authorize', [1.234, 'AFN', $sofort, $url, $customer, $orderId, $metadata], 'authorize', [1.234, 'AFN', $sofort, $url, $customer, $orderId, $metadata]],
            'authAlt'                => ['authorize', [234.1, 'DZD', $sofort, $url], 'authorize', [234.1, 'DZD', $sofort, $url]],
            'authStr'                => ['authorize', [34.12, 'DKK', $paymentTypeId, $url, $customerId, $orderId], 'authorize', [34.12, 'DKK', $paymentTypeId, $url, $customerId, $orderId]],
            'charge'                 => ['charge', [1.234, 'AFN', $sofort, $url, $customer, $orderId, $metadata], 'charge', [1.234, 'AFN', $sofort, $url, $customer, $orderId, $metadata]],
            'chargeAlt'              => ['charge', [234.1, 'DZD', $sofort, $url], 'charge', [234.1, 'DZD', $sofort, $url]],
            'chargeStr'              => ['charge', [34.12, 'DKK', $paymentTypeId, $url, $customerId, $orderId], 'charge', [34.12, 'DKK', $paymentTypeId, $url, $customerId, $orderId]],
            'chargeAuth'             => ['chargeAuthorization', [$payment, 1.234], 'chargeAuthorization', [$payment, 1.234]],
            'chargeAuthAlt'          => ['chargeAuthorization', [$paymentId], 'chargeAuthorization', [$paymentId, null]],
            'chargeAuthStr'          => ['chargeAuthorization', [$paymentId, 2.345], 'chargeAuthorization', [$paymentId, 2.345]],
            'chargePayment'          => ['chargePayment', [$payment, 1.234, 'ALL'], 'chargePayment', [$payment, 1.234, 'ALL']],
            'chargePaymentAlt'       => ['chargePayment', [$payment], 'chargePayment', [$payment]],
            'ship'                   => ['ship', [$payment], 'ship', [$payment]],
            'payout'                 => ['payout', [123, 'EUR', $paymentTypeId, 'url', $customer, $orderId, $metadata, $basket], 'payout', [123, 'EUR', $paymentTypeId, 'url', $customer, $orderId, $metadata, $basket]],
            'initPayPageCharge'      => ['initPayPageCharge', [$paypage, $customer, $basket, $metadata], 'initPayPageCharge', [$paypage, $customer, $basket, $metadata]],
            'initPayPageAuthorize'   => ['initPayPageAuthorize', [$paypage, $customer, $basket, $metadata], 'initPayPageAuthorize', [$paypage, $customer, $basket, $metadata]],
            'fetchDDInstalmentPlans' => ['fetchInstallmentPlans', [123.4567, 'EUR', 4.99, $today], 'fetchInstallmentPlans', [123.4567, 'EUR', 4.99, $today]]
        ];
    }

    /**
     * Provide test data for unzerShouldForwardWebhookActionCallsToTheWebhookService.
     *
     * @return array
     */
    public static function unzerShouldForwardWebhookActionCallsToTheWebhookServiceDP(): array
    {
        $url       = 'https://dev.unzer.com';
        $webhookId = 'webhookId';
        $webhook   = new Webhook();
        $event     = ['event1', 'event2'];

        return [
            'createWebhook' => [ 'createWebhook', [$url, 'event'], 'createWebhook', [$url, 'event'] ],
            'fetchWebhook' => [ 'fetchWebhook', [$webhookId], 'fetchWebhook', [$webhookId] ],
            'fetchWebhook by object' => [ 'fetchWebhook', [$webhook], 'fetchWebhook', [$webhook] ],
            'updateWebhook' => [ 'updateWebhook', [$webhook], 'updateWebhook', [$webhook] ],
            'deleteWebhook' => [ 'deleteWebhook', [$webhookId], 'deleteWebhook', [$webhookId] ],
            'deleteWebhook by object' => [ 'deleteWebhook', [$webhook], 'deleteWebhook', [$webhook] ],
            'fetchAllWebhooks' => [ 'fetchAllWebhooks', [], 'fetchAllWebhooks', [] ],
            'deleteAllWebhooks' => [ 'deleteAllWebhooks', [], 'deleteAllWebhooks', [] ],
            'registerMultipleWebhooks' => ['registerMultipleWebhooks', [$url, $event], 'registerMultipleWebhooks', [$url, $event] ],
            'fetchResourceFromEvent' => ['fetchResourceFromEvent', [], 'fetchResourceFromEvent', [] ]
        ];
    }

    /**
     * @return array
     */
    public static function cancelServiceDP(): array
    {
        $payment       = new Payment();
        $charge        = new Charge();
        $authorization = new Authorization();
        $chargeId      = 'chargeId';
        $paymentId      = 'paymentId';

        return [
            'cancelAuth'             => ['cancelAuthorization', [$authorization, 1.234], 'cancelAuthorization', [$authorization, 1.234]],
            'cancelAuthAlt'          => ['cancelAuthorization', [$authorization], 'cancelAuthorization', [$authorization]],
            'cancelAuthByPayment'    => ['cancelAuthorizationByPayment', [$payment, 1.234], 'cancelAuthorizationByPayment', [$payment, 1.234]],
            'cancelAuthByPaymentAlt' => ['cancelAuthorizationByPayment', [$payment], 'cancelAuthorizationByPayment', [$payment]],
            'cancelAuthByPaymentStr' => ['cancelAuthorizationByPayment', [$paymentId, 234.5], 'cancelAuthorizationByPayment', [$paymentId, 234.5]],
            'cancelChargeById'       => ['cancelChargeById', [$paymentId, $chargeId, 1.234], 'cancelChargeById', [$paymentId, $chargeId, 1.234]],
            'cancelChargeByIdAlt'    => ['cancelChargeById', [$paymentId, $chargeId], 'cancelChargeById', [$paymentId, $chargeId]],
            'cancelCharge'           => ['cancelCharge', [$charge, 1.234], 'cancelCharge', [$charge, 1.234]],
            'cancelChargeAlt'        => ['cancelCharge', [$charge], 'cancelCharge', [$charge]],
        ];
    }

    //</editor-fold>
}
