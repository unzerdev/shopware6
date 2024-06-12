<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and
 * functionality of the Payment resource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;

class PaymentTest extends BaseIntegrationTest
{
    /**
     * Verify fetching payment by authorization.
     *
     * @test
     */
    public function paymentShouldBeFetchableById(): void
    {
        $authorize = $this->createPaypalAuthorization();
        $payment = $this->unzer->fetchPayment($authorize->getPayment()->getId());
        $this->assertNotEmpty($payment->getId());
        $this->assertInstanceOf(Authorization::class, $payment->getAuthorization());
        $this->assertNotEmpty($payment->getAuthorization()->getId());
        $this->assertNotNull($payment->getState());

        $traceId = $authorize->getTraceId();
        $this->assertNotEmpty($traceId);
        $this->assertSame($traceId, $payment->getTraceId());
    }

    /**
     * Verify full charge on payment with authorization.
     *
     * @test
     */
    public function fullChargeShouldBePossibleOnPaymentObject(): void
    {
        $authorization = $this->createCardAuthorization();
        $payment = $authorization->getPayment();

        // pre-check to verify changes due to fullCharge call
        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        /** @var Charge $charge */
        $charge = $payment->charge();
        $paymentNew = $charge->getPayment();

        // verify payment has been updated properly
        $this->assertAmounts($paymentNew, 0.0, 100.0, 100.0, 0.0);
        $this->assertTrue($paymentNew->isCompleted());
    }

    /**
     * Verify payment can be fetched with charges.
     *
     * @test
     */
    public function paymentShouldBeFetchableWithCharges(): void
    {
        $authorize = $this->createCardAuthorization();
        $payment = $authorize->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());
        $this->assertNotNull($payment->getAuthorization());
        $this->assertNotNull($payment->getAuthorization()->getId());

        $charge = $payment->charge();
        $fetchedPayment = $this->unzer->fetchPayment($charge->getPayment()->getId());
        $this->assertNotNull($fetchedPayment->getCharges());
        $this->assertCount(1, $fetchedPayment->getCharges());

        $fetchedCharge = $fetchedPayment->getChargeByIndex(0);
        $this->assertEquals($charge->getAmount(), $fetchedCharge->getAmount());
        $this->assertEquals($charge->getCurrency(), $fetchedCharge->getCurrency());
        $this->assertEquals($charge->getId(), $fetchedCharge->getId());
        $this->assertEquals($charge->getReturnUrl(), $fetchedCharge->getReturnUrl());

        $this->assertEquals($charge->expose(), $fetchedCharge->expose());
    }

    /**
     * Verify partial charge after authorization.
     *
     * @test
     */
    public function partialChargeAfterAuthorization(): void
    {
        $authorization = $this->createCardAuthorization();
        $fetchedPayment = $this->unzer->fetchPayment($authorization->getPayment()->getId());
        $charge = $fetchedPayment->charge(10.0);
        $this->assertNotNull($charge);
        $this->assertEquals('s-chg-1', $charge->getId());
        $this->assertEquals('10.0', $charge->getAmount());
    }

    /**
     * Verify authorization on payment.
     *
     * @test
     */
    public function authorizationShouldBePossibleOnUnzerObject(): void
    {
        /** @var Paypal $paypal */
        $paypal = $this->unzer->createPaymentType(new Paypal());
        $authorize = $this->unzer->authorize(100.0, 'EUR', $paypal, self::RETURN_URL);
        $this->assertNotNull($authorize);
        $this->assertNotEmpty($authorize->getId());
    }

    /**
     * Verify Unzer payment charge is possible using a paymentId.
     *
     * @test
     */
    public function paymentChargeOnAuthorizeShouldBePossibleUsingPaymentId(): void
    {
        $card = $this->unzer->createPaymentType($this->createCardObject());
        $authorization = $this->unzer->authorize(100.00, 'EUR', $card, 'http://unzer.com', null, null, null, null, false);
        $charge = $this->unzer->chargePayment($authorization->getPaymentId());

        $this->assertNotEmpty($charge->getId());
    }

    /**
     * Verify Unzer payment charge is possible using a paymentId and optional ids.
     *
     * @test
     */
    public function paymentChargeOnAuthorizeShouldTakeResourceIds(): void
    {
        $card = $this->unzer->createPaymentType($this->createCardObject());
        $authorization = $this->unzer->authorize(100.00, 'EUR', $card, 'http://unzer.com', null, null, null, null, false);
        $charge = $this->unzer->chargePayment($authorization->getPaymentId(), null, 'o' . self::generateRandomId(), 'i' . self::generateRandomId());

        $this->assertNotEmpty($charge->getId());
    }

    /**
     * Verify Unzer payment charge throws an error if the id does not belong to a payment.
     *
     * @test
     */
    public function chargePaymentShouldThrowErrorOnNonPaymentId(): void
    {
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_PAYMENT_NOT_FOUND);
        $this->unzer->chargePayment('s-crd-xlj0qhdiw40k');
    }

    /**
     * Verify a payment is fetched by orderId if the id is not set.
     *
     * @test
     */
    public function paymentShouldBeFetchedByOrderIdIfIdIsNotSet(): void
    {
        $orderId = str_replace(' ', '', microtime());
        $paypal = $this->unzer->createPaymentType(new Paypal());
        $authorization = $this->unzer->authorize(100.00, 'EUR', $paypal, 'https://unzer.com', null, $orderId, null, null, false);
        $payment = $authorization->getPayment();
        $fetchedPayment = $this->unzer->fetchPaymentByOrderId($orderId);

        $this->assertNotSame($payment, $fetchedPayment);
        $this->assertEquals($payment->expose(), $fetchedPayment->expose());
    }

    /**
     * Verify orderId does not need to be unique.
     *
     * @test
     */
    public function shouldAllowNonUniqueOrderId(): void
    {
        $orderId = 'o' . self::generateRandomId();

        /** @var Card $card */
        $card = $this->unzer->createPaymentType($this->createCardObject());
        $card->charge(1023, 'EUR', self::RETURN_URL, null, $orderId);

        try {
            /** @var Card $card2 */
            $card2 = $this->unzer->createPaymentType($this->createCardObject());
            $card2->charge(1023, 'EUR', self::RETURN_URL, null, $orderId);
            $this->assertTrue(true);
        } catch (UnzerApiException $e) {
            $this->assertTrue(false, "No exception expected here. ({$e->getMerchantMessage()})");
        }
    }

    /**
     * Verify invoiceId does not need to be unique.
     *
     * @test
     */
    public function shouldAllowNonUniqueInvoiceId(): void
    {
        $invoiceId = 'i' . self::generateRandomId();

        /** @var Card $card */
        $card = $this->unzer->createPaymentType($this->createCardObject());
        $card->charge(1023, 'EUR', self::RETURN_URL, null, null, null, null, null, $invoiceId);

        try {
            /** @var Card $card2 */
            $card2 = $this->unzer->createPaymentType($this->createCardObject());
            $card2->charge(1023, 'EUR', self::RETURN_URL, null, null, null, null, null, $invoiceId);
            $this->assertTrue(true);
        } catch (UnzerApiException $e) {
            $this->assertTrue(false, "No exception expected here. ({$e->getMerchantMessage()})");
        }
    }
}
