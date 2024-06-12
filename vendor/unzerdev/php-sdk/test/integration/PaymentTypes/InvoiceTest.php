<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and functionality of the payment method invoice.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\Invoice;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\test\Helper\TestEnvironmentService;

class InvoiceTest extends BaseIntegrationTest
{
    protected function setUp(): void
    {
        $this->getUnzerObject(TestEnvironmentService::getLegacyTestPrivateKey());
    }

    /**
     * Verifies invoice payment type can be created.
     *
     * @test
     */
    public function invoiceTypeShouldBeCreatable(): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->unzer->createPaymentType(new Invoice());
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertNotNull($invoice->getId());
    }

    /**
     * Verify invoice is not authorizable.
     *
     * @test
     */
    public function verifyInvoiceIsNotAuthorizable(): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->unzer->createPaymentType(new Invoice());
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED);

        $this->unzer->authorize(1.0, 'EUR', $invoice, self::RETURN_URL);
    }

    /**
     * Verify invoice is chargeable.
     *
     * @test
     */
    public function verifyInvoiceIsChargeable(): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->unzer->createPaymentType(new Invoice());
        $charge = $this->unzer->charge(20.0, 'EUR', $invoice, self::RETURN_URL);
        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
    }

    /**
     * Verify invoice is not shippable.
     *
     * @test
     */
    public function verifyInvoiceIsNotShippable(): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->unzer->createPaymentType(new Invoice());
        $charge = $invoice->charge(1.0, 'EUR', self::RETURN_URL);
        $this->assertNotEmpty($charge->getIban());
        $this->assertNotEmpty($charge->getBic());
        $this->assertNotEmpty($charge->getHolder());
        $this->assertNotEmpty($charge->getDescriptor());

        $payment = $charge->getPayment();

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_SHIP_NOT_ALLOWED);

        $this->unzer->ship($payment);
    }

    /**
     * Verify invoice charge can be canceled.
     *
     * @test
     */
    public function verifyInvoiceChargeCanBeCanceled(): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->unzer->createPaymentType(new Invoice());
        $charge = $invoice->charge(1.0, 'EUR', self::RETURN_URL);
        $cancellation = $charge->cancel();
        $this->assertNotNull($cancellation);
        $this->assertNotNull($cancellation->getId());
        $payment = $cancellation->getPayment();
        $this->assertTrue($payment->isCanceled());
    }

    /**
     * Verify invoice charge can be canceled.
     *
     * @test
     */
    public function verifyInvoiceChargeCanBePartlyCanceled(): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->unzer->createPaymentType(new Invoice());
        $charge = $invoice->charge(1.0, 'EUR', self::RETURN_URL);
        $cancellation = $charge->cancel(0.5);
        $this->assertNotNull($cancellation);
        $this->assertNotNull($cancellation->getId());
        $payment = $cancellation->getPayment();
        $this->assertTrue($payment->isPending());

        $cancellation2 = $charge->cancel(0.5);
        $this->assertNotNull($cancellation2);
        $this->assertNotNull($cancellation2->getId());
        $payment2 = $cancellation2->getPayment();
        $this->assertTrue($payment2->isCanceled());
    }

    /**
     * Verify that an invoice object can be fetched from the api.
     *
     * @test
     */
    public function invoiceTypeCanBeFetched(): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->unzer->createPaymentType(new Invoice());
        $fetchedInvoice = $this->unzer->fetchPaymentType($invoice->getId());
        $this->assertInstanceOf(Invoice::class, $fetchedInvoice);
        $this->assertEquals($invoice->getId(), $fetchedInvoice->getId());
    }
}
