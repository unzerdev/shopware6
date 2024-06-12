<?php
/*
 * This class defines integration tests to verify interface and
 * functionality of the payment method Applepay.
 *
 *  @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\Applepay;
use UnzerSDK\test\BaseIntegrationTest;

class ApplepayTest extends BaseIntegrationTest
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Testdata need to be updated.');
    }

    /**
     * Verify applepay can be created and fetched.
     *
     * @test
     *
     * @throws UnzerApiException
     */
    public function applepayShouldBeCreatableAndFetchable(): void
    {
        $applepay = $this->createApplepayObject();
        $this->unzer->createPaymentType($applepay);
        $this->assertNotNull($applepay->getId());

        /** @var Applepay $fetchedPaymentTyp */
        $fetchedPaymentTyp = $this->unzer->fetchPaymentType($applepay->getId());
        $this->assertInstanceOf(Applepay::class, $fetchedPaymentTyp);
        $this->assertNull($fetchedPaymentTyp->getVersion());
        $this->assertNull($fetchedPaymentTyp->getData());
        $this->assertNull($fetchedPaymentTyp->getSignature());
        $this->assertNull($fetchedPaymentTyp->getHeader());
    }

    /**
     * Verify that applepay is chargeable
     *
     * @test
     *
     * @throws UnzerApiException
     */
    public function applepayShouldBeChargeable(): void
    {
        $applepay = $this->createApplepayObject();
        $this->unzer->createPaymentType($applepay);
        $charge = $applepay->charge(100.0, 'EUR', self::RETURN_URL);
        $this->assertNotNull($charge->getId());
        $this->assertNull($charge->getRedirectUrl());
    }

    /**
     * Verify that applepay is chargeable
     *
     * @test
     *
     * @throws UnzerApiException
     */
    public function applepayCanBeAuthorized(): void
    {
        $applepay = $this->createApplepayObject();
        $this->unzer->createPaymentType($applepay);
        $authorization = $applepay->authorize(1.0, 'EUR', self::RETURN_URL);

        // verify authorization has been created
        $this->assertNotNull($authorization->getId());
        $this->assertNull($authorization->getRedirectUrl());

        // verify payment object has been created
        $payment = $authorization->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        // verify resources are linked properly
        $this->assertSame($authorization, $payment->getAuthorization());
        $this->assertSame($applepay, $payment->getPaymentType());

        // verify the payment object has been updated properly
        $this->assertAmounts($payment, 1.0, 0.0, 1.0, 0.0);
        $this->assertTrue($payment->isPending());
    }

    /**
     * Verify the applepay can perform charges and creates a payment object doing so.
     *
     * @test
     *
     * @throws UnzerApiException
     */
    public function applepayCanPerformChargeAndCreatesPaymentObject(): void
    {
        $applepay = $this->createApplepayObject();
        /** @var Applepay $applepay */
        $applepay = $this->unzer->createPaymentType($applepay);

        $charge = $applepay->charge(1.0, 'EUR', self::RETURN_URL, null, null, null, null, false);

        // verify charge has been created
        $this->assertNotNull($charge->getId());

        // verify payment object has been created
        $payment = $charge->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        // verify resources are linked properly
        $this->assertEquals($charge->expose(), $payment->getCharge($charge->getId())->expose());
        $this->assertSame($applepay, $payment->getPaymentType());

        // verify the payment object has been updated properly
        $this->assertAmounts($payment, 0.0, 1.0, 1.0, 0.0);
        $this->assertTrue($payment->isCompleted());
    }

    /**
     * Verify the applepay can charge the full amount of the authorization and the payment state is updated accordingly.
     *
     * @test
     *
     * @throws UnzerApiException
     */
    public function fullChargeAfterAuthorize(): void
    {
        $applepay = $this->createApplepayObject();
        $this->unzer->createPaymentType($applepay);

        $authorization = $applepay->authorize(1.0, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment = $authorization->getPayment();

        // pre-check to verify changes due to fullCharge call
        $this->assertAmounts($payment, 1.0, 0.0, 1.0, 0.0);
        $this->assertTrue($payment->isPending());

        $charge     = $this->unzer->chargeAuthorization($payment->getId());
        $paymentNew = $charge->getPayment();

        // verify payment has been updated properly
        $this->assertAmounts($paymentNew, 0.0, 1.0, 1.0, 0.0);
        $this->assertTrue($paymentNew->isCompleted());
    }

    /**
     * Verify the applepay can charge part of the authorized amount and the payment state is updated accordingly.
     *
     * @test
     *
     * @throws UnzerApiException
     */
    public function partialChargeAfterAuthorization(): void
    {
        $applepay          = $this->createApplepayObject();
        /** @var Applepay $applepay */
        $applepay          = $this->unzer->createPaymentType($applepay);
        $authorization = $this->unzer->authorize(
            100.0,
            'EUR',
            $applepay,
            self::RETURN_URL,
            null,
            null,
            null,
            null,
            false
        );

        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        $charge   = $this->unzer->chargeAuthorization($payment->getId(), 20);
        $payment1 = $charge->getPayment();
        $this->assertAmounts($payment1, 80.0, 20.0, 100.0, 0.0);
        $this->assertTrue($payment1->isPartlyPaid());

        $charge   = $this->unzer->chargeAuthorization($payment->getId(), 20);
        $payment2 = $charge->getPayment();
        $this->assertAmounts($payment2, 60.0, 40.0, 100.0, 0.0);
        $this->assertTrue($payment2->isPartlyPaid());

        $charge   = $this->unzer->chargeAuthorization($payment->getId(), 60);
        $payment3 = $charge->getPayment();
        $this->assertAmounts($payment3, 00.0, 100.0, 100.0, 0.0);
        $this->assertTrue($payment3->isCompleted());
    }

    /**
     * Verify that an exception is thrown when trying to charge more than authorized.
     *
     * @test
     *
     * @throws UnzerApiException
     */
    public function exceptionShouldBeThrownWhenChargingMoreThenAuthorized(): void
    {
        $applepay          = $this->createApplepayObject();
        /** @var Applepay $applepay */
        $applepay          = $this->unzer->createPaymentType($applepay);
        $authorization = $applepay->authorize(100.0000, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment       = $authorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        $charge   = $this->unzer->chargeAuthorization($payment->getId(), 50);
        $payment1 = $charge->getPayment();
        $this->assertAmounts($payment1, 50.0, 50.0, 100.0, 0.0);
        $this->assertTrue($payment1->isPartlyPaid());

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_CHARGED_AMOUNT_HIGHER_THAN_EXPECTED);
        $this->unzer->chargeAuthorization($payment->getId(), 70);
    }

    /**
     * Verify applepay authorize can be canceled.
     *
     * @test
     *
     * @throws UnzerApiException
     */
    public function applepayAuthorizeCanBeCanceled(): void
    {
        /** @var Applepay $applepay */
        $applepay      = $this->unzer->createPaymentType($this->createApplepayObject());
        $authorize = $applepay->authorize(100.0, 'EUR', self::RETURN_URL, null, null, null, null, false);

        $cancel = $authorize->cancel();
        $this->assertNotNull($cancel);
        $this->assertNotEmpty($cancel->getId());
    }

    /**
     * Verify the applepay payment can be charged until it is fully charged and the payment is updated accordingly.
     *
     * @test
     *
     * @throws UnzerApiException
     */
    public function partialAndFullChargeAfterAuthorization(): void
    {
        $applepay          = $this->createApplepayObject();
        /** @var Applepay $applepay */
        $applepay          = $this->unzer->createPaymentType($applepay);
        $authorization = $applepay->authorize(100.0000, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment       = $authorization->getPayment();

        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        $charge   = $this->unzer->chargeAuthorization($payment->getId(), 20);
        $payment1 = $charge->getPayment();
        $this->assertAmounts($payment1, 80.0, 20.0, 100.0, 0.0);
        $this->assertTrue($payment1->isPartlyPaid());

        $charge   = $this->unzer->chargeAuthorization($payment->getId());
        $payment2 = $charge->getPayment();
        $this->assertAmounts($payment2, 0.0, 100.0, 100.0, 0.0);
        $this->assertTrue($payment2->isCompleted());
    }

    /**
     * Authorization can be fetched.
     *
     * @test
     *
     * @throws UnzerApiException
     */
    public function authorizationShouldBeFetchable(): void
    {
        $applepay          = $this->createApplepayObject();
        /** @var Applepay $applepay */
        $applepay          = $this->unzer->createPaymentType($applepay);
        $authorization = $applepay->authorize(100.0000, 'EUR', self::RETURN_URL);
        $payment       = $authorization->getPayment();

        $fetchedAuthorization = $this->unzer->fetchAuthorization($payment->getId());
        $this->assertEquals($fetchedAuthorization->getId(), $authorization->getId());
    }

    /**
     * @test
     *
     * @throws UnzerApiException
     */
    public function fullCancelAfterCharge(): void
    {
        $applepay    = $this->createApplepayObject();
        $this->unzer->createPaymentType($applepay);
        $charge  = $applepay->charge(100.0, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment = $charge->getPayment();

        $this->assertAmounts($payment, 0.0, 100.0, 100.0, 0.0);
        $this->assertTrue($payment->isCompleted());

        $payment->cancelAmount();
        $this->assertAmounts($payment, 0.0, 0.0, 100.0, 100.0);
        $this->assertTrue($payment->isCanceled());
    }

    /**
     * Verify a applepay payment can be cancelled after being fully charged.
     *
     * @test
     *
     * @throws UnzerApiException
     */
    public function fullCancelOnFullyChargedPayment(): void
    {
        $applepay = $this->createApplepayObject();
        /** @var Applepay $applepay */
        $applepay = $this->unzer->createPaymentType($applepay);

        $authorization = $applepay->authorize(100.0000, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment       = $authorization->getPayment();

        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        $payment->charge(10.0);
        $this->assertAmounts($payment, 90.0, 10.0, 100.0, 0.0);
        $this->assertTrue($payment->isPartlyPaid());

        $payment->charge(90.0);
        $this->assertAmounts($payment, 0.0, 100.0, 100.0, 0.0);
        $this->assertTrue($payment->isCompleted());

        $cancellation = $payment->cancelAmount();
        $this->assertNotEmpty($cancellation);
        $this->assertAmounts($payment, 0.0, 0.0, 100.0, 100.0);
        $this->assertTrue($payment->isCanceled());
    }

    /**
     * Full cancel on partly charged auth canceled charges.
     *
     * @test
     *
     * @throws UnzerApiException
     */
    public function fullCancelOnPartlyPaidAuthWithCanceledCharges(): void
    {
        $applepay = $this->createApplepayObject();
        /** @var Applepay $applepay */
        $applepay = $this->unzer->createPaymentType($applepay);

        $authorization = $applepay->authorize(100.0000, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment       = $authorization->getPayment();

        $payment->charge(10.0);
        $this->assertAmounts($payment, 90.0, 10.0, 100.0, 0.0);

        $charge = $payment->charge(10.0);
        $this->assertAmounts($payment, 80.0, 20.0, 100.0, 0.0);
        $this->assertTrue($payment->isPartlyPaid());

        $charge->cancel();
        $this->assertAmounts($payment, 80.0, 10.0, 100.0, 10.0);
        $this->assertTrue($payment->isPartlyPaid());

        $payment->cancelAmount();
        $this->assertTrue($payment->isCanceled());
    }

    /**
     * Verify applepay charge can be canceled.
     *
     * @test
     *
     * @throws UnzerApiException
     */
    public function applepayChargeCanBeCanceled(): void
    {
        /** @var Applepay $applepay */
        $applepay   = $this->unzer->createPaymentType($this->createApplepayObject());
        $charge = $applepay->charge(100.0, 'EUR', self::RETURN_URL, null, null, null, null, false);

        $cancel = $charge->cancel();
        $this->assertNotNull($cancel);
        $this->assertNotEmpty($cancel->getId());
    }

    /**
     * @return Applepay
     */
    private function createApplepayObject(): Applepay
    {
        $applepayAutorization = '{
            "version": "EC_v1",
            "data": "EGOe1iZx081t+D0FLES+Ubyr2a3RfDQptIm0ocT223JLk9dxBLZDAMIwnCInsxN6YLGXQzB8Hn1CSeD2VsMr9E3gvl89bcolrP0nvmvgyQPvN5dt3jmF9G7m0OQoiRdOPVS1fA1cJB7dq3Xg0paMPfimdEepCs3GAGvrmF8nEZEq+F5qcouEX0F6AAcyIf5RETrJGZM+V8fG+9FxYh4H4yu8s6N3z5JpAjLx+rl2/fOCMqBBhaHkjhBsCHLpuJL6YMgYHh++AtooBAMOuctnomKdYX+OuxTY1k2S3DF7vvErI/1Daq4e8bbbC/fk8OqYxiDLSNXTzerPPKCjucGDkUlw+ZUIif2BVPDg1a7QsfS0JNNFP4eBWVezZrLt6ce/0cApLaRsimoSOCPYmw==",
            "signature": "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",
            "header": {
                "ephemeralPublicKey": "MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAECjiINGVdR1tbzHQ1jOpdn0+GgSsxRsFkp2uvd7XK+yXNvIj8fAA0JDWZ41VXdg3578qMOu1FMdGbaL7KS28/qw==",
                "publicKeyHash": "zqO5Y3ldWWm4NnIkfGCvJILw30rp3y46Jsf21gE8CNg=",
                "transactionId": "91ce5ad365a5bfaea7db143e407c8b2b183ddbc5f256143a9685c70fedd1ce75"
            }
        }';

        $applepay = new Applepay(null, null, null, null);
        $applepay->handleResponse(json_decode($applepayAutorization));
        return $applepay;
    }
}
