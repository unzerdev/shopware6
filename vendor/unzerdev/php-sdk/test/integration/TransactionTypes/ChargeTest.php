<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify charges in general.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\TransactionTypes;

use UnzerSDK\Constants\RecurrenceTypes;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\InvoiceSecured;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\test\Helper\TestEnvironmentService;

class ChargeTest extends BaseIntegrationTest
{
    /**
     * Verify charge can be performed using the id of a payment type.
     *
     * @test
     */
    public function chargeShouldWorkWithTypeId(): void
    {
        $this->useLegacyKey();
        $paymentType = $this->unzer->createPaymentType(new SepaDirectDebit('DE89370400440532013000'));
        $charge = $this->unzer->charge(100.0, 'EUR', $paymentType->getId(), self::RETURN_URL);
        $this->assertTransactionResourceHasBeenCreated($charge);
        $this->assertInstanceOf(Payment::class, $charge->getPayment());
        $this->assertNotEmpty($charge->getPayment()->getId());
        $this->assertEquals(self::RETURN_URL, $charge->getReturnUrl());
    }

    /**
     * Verify charging with payment type.
     *
     * @test
     */
    public function chargeShouldWorkWithTypeObject(): void
    {
        $this->useLegacyKey();
        $paymentType = $this->unzer->createPaymentType(new SepaDirectDebit('DE89370400440532013000'));
        $charge = $this->unzer->charge(100.0, 'EUR', $paymentType, self::RETURN_URL);
        $this->assertTransactionResourceHasBeenCreated($charge);
        $this->assertInstanceOf(Payment::class, $charge->getPayment());
        $this->assertNotEmpty($charge->getPayment()->getId());
        $this->assertEquals(self::RETURN_URL, $charge->getReturnUrl());
    }

    /**
     * Verify transaction status.
     *
     * @test
     */
    public function chargeStatusIsSetCorrectly(): void
    {
        $this->useLegacyKey();
        $this->assertSuccess($this->createCharge());
    }

    /**
     * Verify charge accepts all parameters.
     *
     * @test
     */
    public function chargeShouldAcceptAllParameters(): void
    {
        // prepare test data
        /** @var Card $paymentType */
        $paymentType = $this->unzer->createPaymentType($this->createCardObject());
        $customer = $this->getMinimalCustomer();
        $orderId = 'o'. self::generateRandomId();
        $metadata = (new Metadata())->addMetadata('key', 'value');
        $basket = $this->createBasket();
        $invoiceId = 'i'. self::generateRandomId();
        $paymentReference = 'paymentReference';

        // perform request
        $recurrenceType = RecurrenceTypes::ONE_CLICK;
        $charge = $paymentType->charge(119.0, 'EUR', self::RETURN_URL, $customer, $orderId, $metadata, $basket, true, $invoiceId, $paymentReference, $recurrenceType);

        // verify the data sent and received match
        $payment = $charge->getPayment();
        $this->assertSame($paymentType, $payment->getPaymentType());
        $this->assertEquals(119.0, $charge->getAmount());
        $this->assertEquals('EUR', $charge->getCurrency());
        $this->assertEquals(self::RETURN_URL, $charge->getReturnUrl());
        $this->assertSame($customer, $payment->getCustomer());
        $this->assertEquals($orderId, $charge->getOrderId());
        $this->assertSame($metadata, $payment->getMetadata());
        $this->assertSame($basket, $payment->getBasket());
        $this->assertTrue($charge->isCard3ds());
        $this->assertEquals($invoiceId, $charge->getInvoiceId());
        $this->assertEquals($paymentReference, $charge->getPaymentReference());
        $this->assertEquals($recurrenceType, $charge->getRecurrenceType());

        // fetch the charge
        $fetchedCharge = $this->unzer->fetchChargeById($charge->getPaymentId(), $charge->getId());

        // verify the fetched transaction matches the initial transaction
        $this->assertEquals($charge->expose(), $fetchedCharge->expose());
        $fetchedPayment = $fetchedCharge->getPayment();
        $this->assertEquals($payment->getPaymentType()->expose(), $fetchedPayment->getPaymentType()->expose());
        $this->assertEquals($payment->getCustomer()->expose(), $fetchedPayment->getCustomer()->expose());
        $this->assertEquals($payment->getMetadata()->expose(), $fetchedPayment->getMetadata()->expose());
        $this->assertEquals($payment->getBasket()->expose(), $fetchedPayment->getBasket()->expose());
    }

    /**
     * Verify requestCharge accepts all parameters.
     *
     * @test
     */
    public function requestChargeShouldAcceptAllParameters(): void
    {
        // prepare test data
        /** @var Card $paymentType */
        $paymentType = $this->unzer->createPaymentType($this->createCardObject());
        $customer = $this->getMinimalCustomer();
        $orderId = 'o'. self::generateRandomId();
        $metadata = (new Metadata())->addMetadata('key', 'value');
        $basket = $this->createBasket();
        $invoiceId = 'i'. self::generateRandomId();
        $paymentReference = 'paymentReference';
        $recurrenceType = RecurrenceTypes::ONE_CLICK;

        // perform request
        $charge = new Charge(119.0, 'EUR', self::RETURN_URL);
        $charge->setRecurrenceType(RecurrenceTypes::ONE_CLICK, $paymentType)
            ->setOrderId($orderId)
            ->setInvoiceId($invoiceId)
            ->setPaymentReference($paymentReference);

        $charge = $this->unzer->performCharge($charge, $paymentType, $customer, $metadata, $basket);

        // verify the data sent and received match
        $payment = $charge->getPayment();
        $this->assertSame($paymentType, $payment->getPaymentType());
        $this->assertEquals(119.0, $charge->getAmount());
        $this->assertEquals('EUR', $charge->getCurrency());
        $this->assertEquals(self::RETURN_URL, $charge->getReturnUrl());
        $this->assertSame($customer, $payment->getCustomer());
        $this->assertEquals($orderId, $charge->getOrderId());
        $this->assertSame($metadata, $payment->getMetadata());
        $this->assertSame($basket, $payment->getBasket());
        $this->assertTrue($charge->isCard3ds());
        $this->assertEquals($invoiceId, $charge->getInvoiceId());
        $this->assertEquals($paymentReference, $charge->getPaymentReference());
        $this->assertEquals($recurrenceType, $charge->getRecurrenceType());

        // fetch the charge
        $fetchedCharge = $this->unzer->fetchChargeById($charge->getPaymentId(), $charge->getId());

        // verify the fetched transaction matches the initial transaction
        $this->assertEquals($charge->expose(), $fetchedCharge->expose());
        $fetchedPayment = $fetchedCharge->getPayment();
        $this->assertEquals($payment->getPaymentType()->expose(), $fetchedPayment->getPaymentType()->expose());
        $this->assertEquals($payment->getCustomer()->expose(), $fetchedPayment->getCustomer()->expose());
        $this->assertEquals($payment->getMetadata()->expose(), $fetchedPayment->getMetadata()->expose());
        $this->assertEquals($payment->getBasket()->expose(), $fetchedPayment->getBasket()->expose());
    }

    /**
     * Verify charge accepts all parameters.
     *
     * @test
     */
    public function chargeWithCustomerShouldAcceptAllParameters(): void
    {
        $this->getUnzerObject()->setKey(TestEnvironmentService::getLegacyTestPrivateKey());
        // prepare test data
        /** @var InvoiceSecured $ivg */
        $ivg = $this->unzer->createPaymentType(new InvoiceSecured());
        $customer = $this->getMaximumCustomer();
        $customer->setShippingAddress($customer->getBillingAddress());
        $orderId = 'o'. self::generateRandomId();
        $metadata = (new Metadata())->addMetadata('key', 'value');
        $basket = $this->createBasket();
        $invoiceId = 'i'. self::generateRandomId();
        $paymentReference = 'paymentReference';

        // perform request
        $charge = $ivg->charge(119.0, 'EUR', self::RETURN_URL, $customer, $orderId, $metadata, $basket, null, $invoiceId, $paymentReference);

        // verify the data sent and received match
        $payment = $charge->getPayment();
        $this->assertSame($ivg, $payment->getPaymentType());
        $this->assertEquals(119.0, $charge->getAmount());
        $this->assertEquals('EUR', $charge->getCurrency());
        $this->assertEquals(self::RETURN_URL, $charge->getReturnUrl());
        $this->assertSame($customer, $payment->getCustomer());
        $this->assertEquals($orderId, $charge->getOrderId());
        $this->assertSame($metadata, $payment->getMetadata());
        $this->assertSame($basket, $payment->getBasket());
        $this->assertEquals($invoiceId, $charge->getInvoiceId());
        $this->assertEquals($paymentReference, $charge->getPaymentReference());

        $fetchedCharge = $this->unzer->fetchChargeById($charge->getPaymentId(), $charge->getId());
        $this->assertEquals($charge->setCard3ds(false)->expose(), $fetchedCharge->expose());
    }

    /**
     * Verify checkoutType for not supported type gets ignored by Api.
     *
     * @test
     */
    public function checkoutTypeGetsIgnordedByApiWithNotSupportedType()
    {
        $paymentType = $this->unzer->createPaymentType($this->createCardObject());
        $charge = new Charge(99.99, 'EUR', self::RETURN_URL);
        $charge->setCheckoutType('express', $paymentType);
        $this->getUnzerObject()->performCharge($charge, $paymentType);

        $fetchedCharge = $this->getUnzerObject()->fetchChargeById(
            $charge->getPayment()->getId(),
            $charge->getId()
        );
        $this->assertTrue($fetchedCharge->isPending());
        $this->assertNull($fetchedCharge->getCheckoutType());
    }
}
