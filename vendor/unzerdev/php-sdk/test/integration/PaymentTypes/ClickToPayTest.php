<?php

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Constants\ExemptionType;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\EmbeddedResources\CardTransactionData;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Clicktopay;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;

class ClickToPayTest extends BaseIntegrationTest
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Skipped by default as setup is missing for integration tests.');
    }

    /**
     * Verify that clickToPay payment type resource can be created.
     *
     * @test
     *
     * @return BasePaymentType
     */
    public function clickToPayShouldBeCreatable(): BasePaymentType
    {
        $clickToPay = $this->createClickToPayObject();

        $this->unzer->createPaymentType($clickToPay);

        $this->assertInstanceOf(Clicktopay::class, $clickToPay);
        $this->assertNotNull($clickToPay->getId());
        $this->assertSame($this->unzer, $clickToPay->getUnzerObject());

        $geoLocation = $clickToPay->getGeoLocation();
        $this->assertNotEmpty($geoLocation->getClientIp());
        $this->assertNotEmpty($geoLocation->getCountryCode());

        return $clickToPay;
    }

    /**
     * Verify that a clickToPay resource can be fetched from the api using its id.
     *
     * @test
     *
     * @depends clickToPayShouldBeCreatable
     *
     * @param mixed $type
     */
    public function googlepayCanBeFetched($type): void
    {
        $this->assertNotNull($type->getId());

        /** @var Clicktopay $fetchedClickToPay */
        $fetchedClickToPay = $this->unzer->fetchPaymentType($type->getId());
        $this->assertNotNull($fetchedClickToPay->getId());
    }

    /**
     * Verify that authorization can be performed with ClickToPay.
     *
     * @test
     *
     * @depends clickToPayShouldBeCreatable
     *
     * @param mixed $type
     */
    public function clickToPayCanPerformAuthorization($type): Authorization
    {
        $authorizationRequest = $this->getLvpAuthorizationObject();
        $authorization = $this->getUnzerObject()
            ->performAuthorization(
                $authorizationRequest,
                $type
            );

        // verify authorization has been created
        $this->assertNotNull($authorization->getId());

        // verify payment object has been created
        $payment = $authorization->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        // verify resources are linked properly
        $this->assertSame($authorization, $payment->getAuthorization());
        $this->assertSame($type, $payment->getPaymentType());

        // verify the payment object has been updated properly
        $this->assertAmounts($payment, 2.99, 0.0, 2.99, 0.0);
        $this->assertTrue($payment->isPending());
        $this->assertTrue($authorization->isSuccess());

        return $authorization;
    }

    /**
     * Verify that authorization can be charged with ClickToPay.
     *
     * @test
     *
     * @depends clickToPayCanPerformAuthorization
     *
     * @param Authorization $authorization
     */
    public function authorizationCanBeCharged(Authorization $authorization): Payment
    {
        $charge = $this->getUnzerObject()
            ->performChargeOnPayment(
                $authorization->getPayment(),
                new Charge()
            );

        // verify charge has been created
        $this->assertNotNull($charge->getId());

        // verify payment object has been created
        $payment = $charge->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        // verify resources are linked properly
        $this->assertSame($charge, $payment->getCharge('s-chg-1'));
        $this->assertSame($authorization->getPayment()->getPaymentType(), $payment->getPaymentType());

        // verify the payment object has been updated properly
        $this->assertAmounts($payment, 0, 2.99, 2.99, 0.0);
        $this->assertTrue($payment->isCompleted());
        $this->assertTrue($charge->isSuccess());

        return $payment;
    }

    /**
     * Verify the clickToPay can perform charges and creates a payment object doing so.
     *
     * @test
     *
     * @depends clickToPayShouldBeCreatable
     *
     * @param mixed $type
     */
    public function canPerformCharge($type): void
    {
        $charge = $this->getUnzerObject()
            ->performCharge(
                $this->getLvpChargeObject(),
                $type
            );

        $fetchedType = $this->unzer->fetchPaymentType($type->getId());

        // verify charge has been created
        $this->assertNotNull($charge->getId());

        // verify payment object has been created
        $payment = $charge->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        // verify resources are linked properly
        $this->assertEquals($charge->expose(), $payment->getCharge($charge->getId())->expose());

        // verify the payment object has been updated properly
        $this->assertAmounts($payment, 0.0, 2.99, 2.99, 0.0);
        $this->assertTrue($payment->isCompleted());
    }

    /**
     * Verify the clickToPay can charge part of the authorized amount and the payment state is updated accordingly.
     *
     * @test
     */
    public function partialChargeAfterAuthorization(): void
    {
        $clickToPay = $this->createClickToPayObject();
        /** @var Clicktopay $clickToPay */
        $clickToPay = $this->unzer->createPaymentType($clickToPay);
        $authorization = $this->getUnzerObject()
            ->performAuthorization(
                $this->getLvpAuthorizationObject(),
                $clickToPay
            );

        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 2.99, 0.0, 2.99, 0.0);
        $this->assertTrue($payment->isPending());

        $charge = $this->unzer->performChargeOnPayment($payment->getId(), new Charge(1));
        $payment1 = $charge->getPayment();
        $this->assertAmounts($payment1, 1.99, 1, 2.99, 0.0);
        $this->assertTrue($payment1->isPartlyPaid());

        $charge = $this->unzer->performChargeOnPayment($payment->getId(), new Charge(1));
        $payment2 = $charge->getPayment();
        $this->assertAmounts($payment2, 0.99, 2, 2.99, 0.0);
        $this->assertTrue($payment2->isPartlyPaid());

        $charge = $this->unzer->performChargeOnPayment($payment->getId(), new Charge(0.99));
        $payment3 = $charge->getPayment();
        $this->assertAmounts($payment3, 00.0, 2.99, 2.99, 0.0);
        $this->assertTrue($payment3->isCompleted());
    }

    /**
     * Verify that an exception is thrown when trying to charge more than authorized.
     *
     * @test
     */
    public function exceptionShouldBeThrownWhenChargingMoreThenAuthorized(): void
    {
        $clickToPay = $this->createClickToPayObject();
        /** @var Clicktopay $clickToPay */
        $clickToPay = $this->unzer->createPaymentType($clickToPay);
        $authorization = $this->getUnzerObject()
            ->performAuthorization(
                $this->getLvpAuthorizationObject(),
                $clickToPay
            );
        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 2.99, 0.0, 2.99, 0.0);
        $this->assertTrue($payment->isPending());

        $charge = $this->unzer->performChargeOnPayment($payment->getId(), new Charge(1.99));
        $payment1 = $charge->getPayment();
        $this->assertAmounts($payment1, 1, 1.99, 2.99, 0.0);
        $this->assertTrue($payment1->isPartlyPaid());

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_CHARGED_AMOUNT_HIGHER_THAN_EXPECTED);
        $this->unzer->performChargeOnPayment($payment->getId(), new Charge(2));
    }

    /**
     * @test
     */
    public function fullCancelAfterCharge(): void
    {
        $clickToPay = $this->createClickToPayObject();
        /** @var Clicktopay $clickToPay */
        $clickToPay = $this->unzer->createPaymentType($clickToPay);
        $charge = $this->getUnzerObject()
            ->performCharge(
                $this->getLvpChargeObject(),
                $clickToPay
            );
        $payment = $charge->getPayment();

        $this->assertAmounts($payment, 0.0, 2.99, 2.99, 0.0);
        $this->assertTrue($payment->isCompleted());

        $this->unzer->cancelChargedPayment($payment);
        $this->assertAmounts($payment, 0.0, 0.0, 2.99, 2.99);
        $this->assertTrue($payment->isCanceled());
    }

    /** Creates an authorization request object with low value payment(lvp) set to avoid 3ds challenge.
     *
     * @return Authorization
     */
    protected function getLvpAuthorizationObject()
    {
        return (new Authorization(2.99, 'EUR', self::RETURN_URL))
            ->setCardTransactionData(
                (new CardTransactionData())->setExemptionType(ExemptionType::LOW_VALUE_PAYMENT)
            );
    }

    /** Creates an charge request object with low value payment(lvp) set to avoid 3ds challenge.
     *
     * @return Charge
     */
    protected function getLvpChargeObject()
    {
        return (new Charge(2.99, 'EUR', self::RETURN_URL))
            ->setCardTransactionData(
                (new CardTransactionData())->setExemptionType(ExemptionType::LOW_VALUE_PAYMENT)
            );
    }

    protected function createClickToPayObject(): Clicktopay
    {
        $clickToPay = (new Clicktopay());
        $this->assertNull($clickToPay->getId());

        $geoLocation = $clickToPay->getGeoLocation();
        $this->assertNull($geoLocation->getClientIp());
        $this->assertNull($geoLocation->getCountryCode());


        $clickToPay->setBrand("mastercard")
            ->setMcCorrelationId("corr12345")
            ->setMcMerchantTransactionId("0a4e0d3.34f4a04b.894125b16ddd1f1b3a58273d63a0894179ac3535")
            ->setMcCxFlowId("34f4a04b.5ab95e32-30f7-483f-846f-a08230a6d2ed.1618397078");

        return $clickToPay;
    }
}
