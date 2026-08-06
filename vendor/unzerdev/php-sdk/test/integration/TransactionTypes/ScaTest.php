<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify SCA transactions in general.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\TransactionTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Sca;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\test\Helper\TestEnvironmentService;

/**
 * Verify that PAPI endpoints can be called correctly
 * GET {domain}/v1/payments/{paymentId}/sca/{scaId}
 * POST {domain}/v1/payments/sca
 * POST {domain}/v1/payments/{paymentId}/sca/authorize
 * POST {domain}/v1/payments/{paymentId}/sca/charges
 */
class ScaTest extends BaseIntegrationTest
{
    protected function setUp(): void
    {
        $this->getUnzerObject(TestEnvironmentService::getUnzerOneTestPrivateKey());
    }
    /**
     * Verify SCA transaction can be performed using the id of a payment type.
     *
     * @test
     */
    public function scaShouldWorkWithTypeId(): Sca
    {
        /** @var Card $paymentType */
        $paymentType = $this->unzer->createPaymentType($this->createCardObject());
        $sca = new Sca(100.0, 'EUR', self::RETURN_URL);
        $sca = $this->unzer->performSca($sca, $paymentType->getId());

        $this->assertTransactionResourceHasBeenCreated($sca);
        $this->assertInstanceOf(Payment::class, $sca->getPayment());
        $this->assertNotEmpty($sca->getPayment()->getId());
        $this->assertEquals(self::RETURN_URL, $sca->getReturnUrl());

        return $sca;
    }

    /**
     * Verify SCA transaction can be performed with payment type object.
     *
     * @test
     */
    public function scaShouldWorkWithTypeObject(): Sca
    {
        /** @var Card $paymentType */
        $paymentType = $this->unzer->createPaymentType($this->createCardObject());
        $sca = new Sca(100.0, 'EUR', self::RETURN_URL);
        $sca = $this->unzer->performSca($sca, $paymentType);

        $this->assertTransactionResourceHasBeenCreated($sca);
        $this->assertInstanceOf(Payment::class, $sca->getPayment());
        $this->assertNotEmpty($sca->getPayment()->getId());
        $this->assertEquals(self::RETURN_URL, $sca->getReturnUrl());

        return $sca;
    }

    /**
     * Verify SCA transaction accepts all parameters.
     *
     * @test
     */
    public function scaShouldAcceptAllParameters(): Sca
    {
        // prepare test data
        /** @var Card $paymentType */
        $paymentType = $this->unzer->createPaymentType($this->createCardObject());
        $customer = $this->getMinimalCustomer();
        $metadata = (new Metadata())->addMetadata('key', 'value');
        $basket = $this->createBasket();
        $paymentReference = 'scaPaymentReference';

        // perform request
        $sca = new Sca(119.0, 'EUR', self::RETURN_URL);
        $sca->setPaymentReference($paymentReference);

        $sca = $this->unzer->performSca($sca, $paymentType, $customer, $metadata, $basket);

        // verify the data sent and received match
        $payment = $sca->getPayment();
        $this->assertSame($paymentType, $payment->getPaymentType());
        $this->assertEquals(119.0, $sca->getAmount());
        $this->assertEquals('EUR', $sca->getCurrency());
        $this->assertEquals(self::RETURN_URL, $sca->getReturnUrl());
        $this->assertSame($customer, $payment->getCustomer());
        $this->assertSame($metadata, $payment->getMetadata());
        $this->assertSame($basket, $payment->getBasket());
        $this->assertEquals($paymentReference, $sca->getPaymentReference());

        return $sca;
    }

    /**
     * Verify SCA transaction can be fetched.
     *
     * @test
     * @depends scaShouldWorkWithTypeId
     */
    public function scaTransactionCanBeFetched($sca): void
    {
        $this->assertTransactionResourceHasBeenCreated($sca);

        // Fetch the SCA transaction
        $fetchedSca = $this->unzer->fetchScaById($sca->getPaymentId(), $sca->getId());

        // Verify the fetched transaction matches the initial transaction
        $this->assertEquals($sca->getId(), $fetchedSca->getId());
        $this->assertEquals($sca->getPaymentId(), $fetchedSca->getPaymentId());
        $this->assertEquals($sca->getAmount(), $fetchedSca->getAmount());
        $this->assertEquals($sca->getCurrency(), $fetchedSca->getCurrency());
    }

    /**
     * Verify SCA transaction can be fetched using Sca object.
     *
     * @test
     *
     * @depends scaShouldWorkWithTypeId
     */
    public function scaTransactionCanBeFetchedUsingSca($sca): void
    {
        $this->assertTransactionResourceHasBeenCreated($sca);

        // Fetch the SCA transaction using the Sca object
        $fetchedSca = $this->unzer->fetchSca($sca);

        // Verify the fetched transaction matches the initial transaction
        $this->assertEquals($sca->getId(), $fetchedSca->getId());
        $this->assertEquals($sca->getAmount(), $fetchedSca->getAmount());
        $this->assertEquals($sca->getCurrency(), $fetchedSca->getCurrency());
    }

    /**
     * Verify charge can be performed on SCA transaction.
     *
     * @test
     *
     * @depends scaShouldWorkWithTypeId
     */
    public function chargeFailsOnPendingSCATransactionWithErrorCode($sca): void
    {
        $charge = new Charge(100, "EUR");

        // Expect charge to fail on pending transaction with error code
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_CANNOT_CHARGE_UNSUCCESSFUL_SCA_TRANSACTION);
        $this->expectExceptionMessage('Cannot perform payment on an unsuccessful strong customer authentication.');

        $this->unzer->chargeScaTransaction($sca->getPayment(), $charge);
    }

    /**
     * Verify authorize can be performed on SCA transaction.
     *
     * @test
     *
     * @depends scaShouldWorkWithTypeObject
     */
    public function authorizeCanBePerformedOnScaTransaction($sca): void
    {
        $authorization = new Authorization(100, "EUR");

        // Expect authorization to fail on pending transaction with error code
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_CANNOT_CHARGE_UNSUCCESSFUL_SCA_TRANSACTION);
        $this->expectExceptionMessage('Cannot perform payment on an unsuccessful strong customer authentication.');

        // Perform an authorize on SCA transaction
        $authorization = $this->unzer->authorizeScaTransaction($sca->getPayment(), $authorization);
    }
}
