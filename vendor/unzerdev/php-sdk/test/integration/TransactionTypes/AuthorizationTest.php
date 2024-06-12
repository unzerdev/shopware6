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
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\test\BaseIntegrationTest;

class AuthorizationTest extends BaseIntegrationTest
{
    /**
     * Verify Unzer object can perform an authorization based on the paymentTypeId.
     *
     * @test
     */
    public function authorizeWithTypeId(): void
    {
        $paymentType = $this->unzer->createPaymentType(new Paypal());
        $authorize = $this->unzer->authorize(100.0, 'EUR', $paymentType->getId(), self::RETURN_URL);
        $this->assertNotNull($authorize);
        $this->assertNotEmpty($authorize->getId());
        $this->assertNotEmpty($authorize->getUniqueId());
        $this->assertNotEmpty($authorize->getShortId());

        $traceId = $authorize->getTraceId();
        $this->assertNotEmpty($traceId);
        $this->assertSame($traceId, $authorize->getPayment()->getTraceId());
    }

    /**
     * Verify Unzer object can perform an authorization based on the paymentType object.
     *
     * @test
     */
    public function authorizeWithType(): void
    {
        $paymentType = $this->unzer->createPaymentType(new Paypal());
        $authorize = $this->unzer->authorize(100.0, 'EUR', $paymentType, self::RETURN_URL);
        $this->assertNotNull($authorize);
        $this->assertNotNull($authorize->getId());
    }

    /**
     * Verify authorization produces Payment and Customer.
     *
     * @test
     */
    public function authorizationProducesPaymentAndCustomer(): void
    {
        $paymentType = $this->unzer->createPaymentType(new Paypal());
        $customer = $this->getMinimalCustomer();
        $this->assertNull($customer->getId());

        $authorize = $this->unzer->authorize(100.0, 'EUR', $paymentType, self::RETURN_URL, $customer);
        $payment = $authorize->getPayment();
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
        $paymentType = $this->unzer->createPaymentType(new Paypal());
        $customerId  = $this->unzer->createCustomer($this->getMinimalCustomer())->getId();
        $orderId     = microtime(true);
        $authorize   = $this->unzer->authorize(100.0, 'EUR', $paymentType, self::RETURN_URL, $customerId, $orderId);
        $payment     = $authorize->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        $newCustomer = $payment->getCustomer();
        $this->assertNotNull($newCustomer);
        $this->assertNotNull($newCustomer->getId());

        return $authorize;
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
        $this->assertEquals($authorization->setCard3ds(false)->expose(), $fetchedAuthorization->expose());
    }

    /**
     * Verify authorization has the expected states.
     *
     * @test
     *
     * @dataProvider authorizeHasExpectedStatesDP
     *
     * @param BasePaymentType|AbstractUnzerResource $paymentType
     * @param string                                $expectedState The state the transaction is expected to be in.
     */
    public function authorizeHasExpectedStates(BasePaymentType $paymentType, $expectedState): void
    {
        $paymentType = $this->unzer->createPaymentType($paymentType);
        $authorize = $this->unzer->authorize(100.0, 'EUR', $paymentType->getId(), self::RETURN_URL, null, null, null, null, false);

        $stateCheck = 'assert' . ucfirst($expectedState);
        $this->$stateCheck($authorize);
    }

    /**
     * Verify authorize accepts all parameters.
     *
     * @test
     */
    public function authorizeShouldAcceptAllParameters(): void
    {
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($this->createCardObject());
        $customer = $this->getMinimalCustomer();
        $orderId = 'o' . self::generateRandomId();
        $metadata = (new Metadata())->addMetadata('key', 'value');
        $basket = $this->createBasket();
        $invoiceId = 'i' . self::generateRandomId();
        $paymentReference = 'paymentReference';

        $authorize = $card->authorize(119.0, 'EUR', self::RETURN_URL, $customer, $orderId, $metadata, $basket, true, $invoiceId, $paymentReference, RecurrenceTypes::ONE_CLICK);
        $payment = $authorize->getPayment();

        $this->assertSame($card, $payment->getPaymentType());
        $this->assertEquals(119.0, $authorize->getAmount());
        $this->assertEquals('EUR', $authorize->getCurrency());
        $this->assertEquals(self::RETURN_URL, $authorize->getReturnUrl());
        $this->assertSame($customer, $payment->getCustomer());
        $this->assertEquals($orderId, $authorize->getOrderId());
        $this->assertSame($metadata, $payment->getMetadata());
        $this->assertSame($basket, $payment->getBasket());
        $this->assertTrue($authorize->isCard3ds());
        $this->assertEquals($invoiceId, $authorize->getInvoiceId());
        $this->assertEquals($paymentReference, $authorize->getPaymentReference());

        $fetchedAuthorize = $this->unzer->fetchAuthorization($authorize->getPaymentId());
        $fetchedPayment = $fetchedAuthorize->getPayment();

        $this->assertEquals($payment->getPaymentType()->expose(), $fetchedPayment->getPaymentType()->expose());
        $this->assertEquals($authorize->getAmount(), $fetchedAuthorize->getAmount());
        $this->assertEquals($authorize->getCurrency(), $fetchedAuthorize->getCurrency());
        $this->assertEquals($authorize->getReturnUrl(), $fetchedAuthorize->getReturnUrl());
        $this->assertEquals($payment->getCustomer()->expose(), $fetchedPayment->getCustomer()->expose());
        $this->assertEquals($authorize->getOrderId(), $fetchedAuthorize->getOrderId());
        $this->assertEquals($payment->getMetadata()->expose(), $fetchedPayment->getMetadata()->expose());
        $this->assertEquals($payment->getBasket()->expose(), $fetchedPayment->getBasket()->expose());
        $this->assertEquals($authorize->isCard3ds(), $fetchedAuthorize->isCard3ds());
        $this->assertEquals($authorize->getInvoiceId(), $fetchedAuthorize->getInvoiceId());
        $this->assertEquals($authorize->getPaymentReference(), $fetchedAuthorize->getPaymentReference());
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

        $authorize = new Authorization(119.0, 'EUR', self::RETURN_URL);
        $authorize->setRecurrenceType(RecurrenceTypes::ONE_CLICK, $card)
            ->setOrderId($orderId)
            ->setInvoiceId($invoiceId)
            ->setPaymentReference($paymentReference);

        $authorize = $this->unzer->performAuthorization($authorize, $card, $customer, $metadata, $basket);
        $payment = $authorize->getPayment();

        $this->assertSame($card, $payment->getPaymentType());
        $this->assertEquals(119.0, $authorize->getAmount());
        $this->assertEquals('EUR', $authorize->getCurrency());
        $this->assertEquals(self::RETURN_URL, $authorize->getReturnUrl());
        $this->assertSame($customer, $payment->getCustomer());
        $this->assertEquals($orderId, $authorize->getOrderId());
        $this->assertSame($metadata, $payment->getMetadata());
        $this->assertSame($basket, $payment->getBasket());
        $this->assertTrue($authorize->isCard3ds());
        $this->assertEquals($invoiceId, $authorize->getInvoiceId());
        $this->assertEquals($paymentReference, $authorize->getPaymentReference());

        $fetchedAuthorize = $this->unzer->fetchAuthorization($authorize->getPaymentId());
        $fetchedPayment = $fetchedAuthorize->getPayment();

        $this->assertEquals($payment->getPaymentType()->expose(), $fetchedPayment->getPaymentType()->expose());
        $this->assertEquals($authorize->getAmount(), $fetchedAuthorize->getAmount());
        $this->assertEquals($authorize->getCurrency(), $fetchedAuthorize->getCurrency());
        $this->assertEquals($authorize->getReturnUrl(), $fetchedAuthorize->getReturnUrl());
        $this->assertEquals($payment->getCustomer()->expose(), $fetchedPayment->getCustomer()->expose());
        $this->assertEquals($authorize->getOrderId(), $fetchedAuthorize->getOrderId());
        $this->assertEquals($payment->getMetadata()->expose(), $fetchedPayment->getMetadata()->expose());
        $this->assertEquals($payment->getBasket()->expose(), $fetchedPayment->getBasket()->expose());
        $this->assertEquals($authorize->isCard3ds(), $fetchedAuthorize->isCard3ds());
        $this->assertEquals($authorize->getInvoiceId(), $fetchedAuthorize->getInvoiceId());
        $this->assertEquals($authorize->getPaymentReference(), $fetchedAuthorize->getPaymentReference());
    }

    //<editor-fold desc="Data Providers">

    /**
     * @return array
     */
    public function authorizeHasExpectedStatesDP(): array
    {
        return [
            'card' => [$this->createCardObject(), 'success'],
            'paypal' => [new Paypal(), 'pending']
        ];
    }

    //</editor-fold>
}
