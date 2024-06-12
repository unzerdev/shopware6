<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and functionality of the payment method Invoice Secured.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\InvoiceSecured;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\test\Helper\TestEnvironmentService;

/**
 * @deprecated since 1.2.0.0 PaylaterInvoice should be used instead in the future.
 */
class InvoiceGuaranteedTest extends BaseIntegrationTest
{
    protected function setUp(): void
    {
        $this->getUnzerObject(TestEnvironmentService::getLegacyTestPrivateKey());
    }

    /**
     * Verify, backwards compatibility regarding fetching payment type and map it to invoice secured class.
     *
     * @test
     */
    public function ivgTypeShouldBeFetchable(): InvoiceSecured
    {
        $ivgMock = $this->getMockBuilder(InvoiceSecured::class)->setMethods(['getUri'])->getMock();
        $ivgMock->method('getUri')->willReturn('/types/invoice-guaranteed');

        /** @var InvoiceSecured $ivgType */
        $ivgType = $this->unzer->createPaymentType($ivgMock);
        $this->assertInstanceOf(InvoiceSecured::class, $ivgType);
        $this->assertMatchesRegularExpression('/^s-ivg-[.]*/', $ivgType->getId());

        $fetchedType = $this->unzer->fetchPaymentType($ivgType->getId());
        $this->assertInstanceOf(InvoiceSecured::class, $fetchedType);
        $this->assertMatchesRegularExpression('/^s-ivg-[.]*/', $fetchedType->getId());

        return $fetchedType;
    }

    /**
     * Verify fetched ivg type can be charged
     *
     * @test
     *
     * @depends ivgTypeShouldBeFetchable
     *
     * @param InvoiceSecured $ivgType fetched ivg type.
     *
     * @throws UnzerApiException
     */
    public function ivgTypeShouldBeChargable(InvoiceSecured $ivgType)
    {
        $customer = $this->getMaximumCustomer();
        $charge = $ivgType->charge(100.00, 'EUR', 'https://unzer.com', $customer);

        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
        $this->assertTrue($charge->isPending());

        return $charge;
    }

    /**
     * Verify fetched ivg type can be shipped.
     *
     * @test
     *
     * @depends ivgTypeShouldBeChargable
     */
    public function ivgTypeShouldBeShippable(Charge $ivgCharge)
    {
        $invoiceId = 'i' . self::generateRandomId();

        $ship = $this->unzer->ship($ivgCharge->getPayment(), $invoiceId);
        // expect Payment to be pending after shipment.
        $this->assertTrue($ship->getPayment()->isPending());
        $this->assertNotNull($ship);
    }
}
