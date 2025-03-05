<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and
 * functionality of the authorization transaction type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\TransactionTypes;

use UnzerSDK\Constants\RecurrenceTypes;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\PreAuthorization;
use UnzerSDK\test\BaseIntegrationTest;

/**
 * @group CC-1576
 */
class PreAuthorizationTest extends BaseIntegrationTest
{
    /**
     * Verify Unzer object can perform an authorization based on the paymentTypeId.
     *
     * @test
     */
    public function authorizeWithTypeId(): void
    {
        $paymentType = $this->unzer->createPaymentType($this->createCardObject());
        $preauth = new PreAuthorization(100.0, 'EUR', self::RETURN_URL);
        $this->unzer->performAuthorization($preauth, $paymentType->getId());
        $this->assertNotNull($preauth);
        $this->assertNotEmpty($preauth->getId());
        $this->assertNotEmpty($preauth->getUniqueId());
        $this->assertNotEmpty($preauth->getShortId());

        $traceId = $preauth->getTraceId();
        $this->assertNotEmpty($traceId);
        $this->assertSame($traceId, $preauth->getPayment()->getTraceId());
        $this->assertPending($preauth);
    }

    /**
     * Verify authorization produces Payment and Customer.
     *
     * @test
     */
    public function authorizationProducesPaymentAndCustomer(): void
    {
        $paymentType = $this->unzer->createPaymentType($this->createCardObject());

        $customer = $this->getMinimalCustomer();
        $this->assertNull($customer->getId());

        $preauth = new PreAuthorization(100.0, 'EUR', self::RETURN_URL);
        $this->unzer->performAuthorization($preauth, $paymentType, $customer);
        $payment = $preauth->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        $newCustomer = $payment->getCustomer();
        $this->assertNotNull($newCustomer);
        $this->assertNotNull($newCustomer->getId());
    }

    /**
     * Verify authorization with customer Id.
     *
     * @test
     *
     * @return Authorization
     */
    public function authorizationWithCustomerId(): Authorization
    {
        $paymentType = $this->unzer->createPaymentType($this->createCardObject());

        $customerId = $this->unzer->createCustomer($this->getMinimalCustomer())->getId();
        $orderId = microtime(true);
        $preauth = (new PreAuthorization(100.0, 'EUR', self::RETURN_URL))->setOrderId($orderId);
        $this->unzer->performAuthorization($preauth, $paymentType, $customerId);
        $payment = $preauth->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        $newCustomer = $payment->getCustomer();
        $this->assertNotNull($newCustomer);
        $this->assertNotNull($newCustomer->getId());

        return $preauth;
    }

    /**
     * Verify authorization can be fetched.
     *
     * @depends authorizationWithCustomerId
     *
     * @test
     *
     * @param Authorization $authorization
     */
    public function authorizationCanBeFetched(Authorization $authorization): void
    {
        $fetchedAuthorization = $this->unzer->fetchAuthorization($authorization->getPaymentId());
        $this->assertInstanceOf(PreAuthorization::class, $fetchedAuthorization);
        $this->assertEquals($authorization->setCard3ds(true)->expose(), $fetchedAuthorization->expose());
    }


    /**
     * Verify authorize accepts all parameters.
     *
     * @test
     */
    public function requestAuthorizationShouldAcceptAllParameters(): void
    {
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($this->createCardObject());
        $customer = $this->getMinimalCustomer();
        $orderId = 'o' . self::generateRandomId();
        $metadata = (new Metadata())->addMetadata('key', 'value');
        $basket = $this->createBasket();
        $invoiceId = 'i' . self::generateRandomId();
        $paymentReference = 'paymentReference';

        $preauth = new PreAuthorization(119.0, 'EUR', self::RETURN_URL);
        $preauth->setRecurrenceType(RecurrenceTypes::ONE_CLICK, $card)
            ->setOrderId($orderId)
            ->setInvoiceId($invoiceId)
            ->setPaymentReference($paymentReference);

        $preauth = $this->unzer->performAuthorization($preauth, $card, $customer, $metadata, $basket);
        $payment = $preauth->getPayment();

        $this->assertSame($card, $payment->getPaymentType());
        $this->assertEquals(119.0, $preauth->getAmount());
        $this->assertEquals('EUR', $preauth->getCurrency());
        $this->assertEquals(self::RETURN_URL, $preauth->getReturnUrl());
        $this->assertSame($customer, $payment->getCustomer());
        $this->assertEquals($orderId, $preauth->getOrderId());
        $this->assertSame($metadata, $payment->getMetadata());
        $this->assertSame($basket, $payment->getBasket());
        $this->assertTrue($preauth->isCard3ds());
        $this->assertEquals($invoiceId, $preauth->getInvoiceId());
        $this->assertEquals($paymentReference, $preauth->getPaymentReference());

        $fetchedAuthorize = $this->unzer->fetchAuthorization($preauth->getPaymentId());
        $fetchedPayment = $fetchedAuthorize->getPayment();

        $this->assertEquals($payment->getPaymentType()->expose(), $fetchedPayment->getPaymentType()->expose());
        $this->assertEquals($preauth->getAmount(), $fetchedAuthorize->getAmount());
        $this->assertEquals($preauth->getCurrency(), $fetchedAuthorize->getCurrency());
        $this->assertEquals($preauth->getReturnUrl(), $fetchedAuthorize->getReturnUrl());
        $this->assertEquals($payment->getCustomer()->expose(), $fetchedPayment->getCustomer()->expose());
        $this->assertEquals($preauth->getOrderId(), $fetchedAuthorize->getOrderId());
        $this->assertEquals($payment->getMetadata()->expose(), $fetchedPayment->getMetadata()->expose());
        $this->assertEquals($payment->getBasket()->expose(), $fetchedPayment->getBasket()->expose());
        $this->assertEquals($preauth->isCard3ds(), $fetchedAuthorize->isCard3ds());
        $this->assertEquals($preauth->getInvoiceId(), $fetchedAuthorize->getInvoiceId());
        $this->assertEquals($preauth->getPaymentReference(), $fetchedAuthorize->getPaymentReference());
    }

    //<editor-fold desc="Data Providers">

    /**
     * @return array
     */
    public function authorizeHasExpectedStatesDP(): array
    {
        return [
            'card' => [$this->createCardObject(), 'pending'],
            'paypal' => [new Paypal(), 'pending']
        ];
    }

    //</editor-fold>
}
