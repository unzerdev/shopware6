<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and
 * functionality of the payment method sepa direct debit secured.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebitSecured;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\test\Helper\TestEnvironmentService;

class SepaDirectDebitSecuredTest extends BaseIntegrationTest
{
    protected function setUp(): void
    {
        $this->getUnzerObject(TestEnvironmentService::getLegacyTestPrivateKey());
    }

    /**
     * Verify sepa direct debit secured can be created with mandatory fields only.
     *
     * @test
     */
    public function sepaDirectDebitSecuredShouldBeCreatableWithMandatoryFieldsOnly(): void
    {
        $directDebitSecured = new SepaDirectDebitSecured('DE89370400440532013000');
        /** @var SepaDirectDebitSecured $directDebitSecured */
        $directDebitSecured = $this->unzer->createPaymentType($directDebitSecured);
        $this->assertInstanceOf(SepaDirectDebitSecured::class, $directDebitSecured);
        $this->assertNotNull($directDebitSecured->getId());

        /** @var SepaDirectDebitSecured $fetchedDirectDebitSecured */
        $fetchedDirectDebitSecured = $this->unzer->fetchPaymentType($directDebitSecured->getId());
        $this->assertEquals($directDebitSecured->expose(), $fetchedDirectDebitSecured->expose());
    }

    /**
     * Verify sepa direct debit secured can be created.
     *
     * @test
     *
     * @return SepaDirectDebitSecured
     */
    public function sepaDirectDebitSecuredShouldBeCreatable(): SepaDirectDebitSecured
    {
        $directDebitSecured = (new SepaDirectDebitSecured('DE89370400440532013000'))->setHolder('John Doe')->setBic('COBADEFFXXX');
        /** @var SepaDirectDebitSecured $directDebitSecured */
        $directDebitSecured = $this->unzer->createPaymentType($directDebitSecured);
        $this->assertInstanceOf(SepaDirectDebitSecured::class, $directDebitSecured);
        $this->assertNotNull($directDebitSecured->getId());

        /** @var SepaDirectDebitSecured $fetchedDirectDebitSecured */
        $fetchedDirectDebitSecured = $this->unzer->fetchPaymentType($directDebitSecured->getId());
        $this->assertEquals($directDebitSecured->expose(), $fetchedDirectDebitSecured->expose());

        return $fetchedDirectDebitSecured;
    }

    /**
     * Verify Sepa Direct Debit Secured needs a basket object
     *
     * @test
     *
     * @depends sepaDirectDebitSecuredShouldBeCreatable
     *
     * @param sepaDirectDebitSecured $sepaDirectDebitSecured
     */
    public function sepaDirectDebitSecuredRequiresBasket(SepaDirectDebitSecured $sepaDirectDebitSecured): void
    {
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_FACTORING_REQUIRES_BASKET);
        $this->unzer->charge(1.0, 'EUR', $sepaDirectDebitSecured, self::RETURN_URL);
    }

    /**
     * Verify Sepa Direct Debit Secured needs a customer object
     *
     * @test
     *
     * @depends sepaDirectDebitSecuredShouldBeCreatable
     *
     * @param sepaDirectDebitSecured $sepaDirectDebitSecured
     */
    public function sepaDirectDebitSecuredRequiresCustomer(SepaDirectDebitSecured $sepaDirectDebitSecured): void
    {
        $basket = $this->createBasket();
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_FACTORING_REQUIRES_CUSTOMER);
        $this->unzer->charge(1.0, 'EUR', $sepaDirectDebitSecured, self::RETURN_URL, null, null, null, $basket);
    }

    /**
     * Verify authorization is not allowed for sepa direct debit secured.
     *
     * @test
     *
     * @param SepaDirectDebitSecured $directDebitSecured
     *
     * @depends sepaDirectDebitSecuredShouldBeCreatable
     */
    public function directDebitSecuredShouldProhibitAuthorization(SepaDirectDebitSecured $directDebitSecured): void
    {
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED);

        $this->unzer->authorize(1.0, 'EUR', $directDebitSecured, self::RETURN_URL);
    }

    /**
     * Verify direct debit secured can be charged.
     *
     * @test
     */
    public function directDebitSecuredShouldAllowCharge(): void
    {
        $directDebitSecured = (new SepaDirectDebitSecured('DE89370400440532013000'))->setBic('COBADEFFXXX');
        $this->unzer->createPaymentType($directDebitSecured);

        $customer = $this->getMaximumCustomerInclShippingAddress()->setShippingAddress($this->getBillingAddress());
        $basket = $this->createBasket();
        $charge   = $directDebitSecured->charge(100.0, 'EUR', self::RETURN_URL, $customer, null, null, $basket);
        $this->assertTransactionResourceHasBeenCreated($charge);
    }

    /**
     * Verify ddg will throw error if addresses do not match.
     *
     * @test
     */
    public function ddgShouldThrowErrorIfAddressesDoNotMatch(): void
    {
        $directDebitSecured = (new SepaDirectDebitSecured('DE89370400440532013000'))->setBic('COBADEFFXXX');
        $this->unzer->createPaymentType($directDebitSecured);

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_ADDRESSES_DO_NOT_MATCH);

        $customer = $this->getMaximumCustomerInclShippingAddress();
        $basket = $this->createBasket();
        $directDebitSecured->charge(100.0, 'EUR', self::RETURN_URL, $customer, null, null, $basket);
    }

    /**
     * Verify, backwards compatibility regarding fetching payment type and map it to direct debit secured class.
     *
     * @test
     */
    public function ddgTypeShouldBeFechable(): SepaDirectDebitSecured
    {
        // Mock a ddg Type
        $ddgMock = $this->getMockBuilder(SepaDirectDebitSecured::class)
            ->setMethods(['getUri'])
            ->setConstructorArgs(['DE89370400440532013000'])
            ->getMock();
        $ddgMock->method('getUri')->willReturn('/types/sepa-direct-debit-guaranteed');

        // When
        /** @var SepaDirectDebitSecured $insType */
        $this->unzer->createPaymentType($ddgMock);
        $this->assertMatchesRegularExpression('/^s-ddg-[.]*/', $ddgMock->getId());

        // Then
        $fetchedType = $this->unzer->fetchPaymentType($ddgMock->getId());
        $this->assertInstanceOf(SepaDirectDebitSecured::class, $fetchedType);
        $this->assertMatchesRegularExpression('/^s-ddg-[.]*/', $fetchedType->getId());

        return $fetchedType;
    }

    /**
     * Verify fetched ddg type can be charged
     *
     * @test
     *
     * @depends ddgTypeShouldBeFechable
     *
     * @param SepaDirectDebitSecured $ddgType fetched ins type.
     *
     * @throws UnzerApiException
     */
    public function ddgTypeCharge(SepaDirectDebitSecured $ddgType)
    {
        $customer = $this->getMaximumCustomer();
        $basket = $this->createBasket();

        /** @var Charge $auth */
        $charge = $ddgType->charge(119.00, 'EUR', 'https://unzer.com', $customer, null, null, $basket);
        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
        $this->assertTrue($charge->isSuccess());

        return $charge;
    }

    /**
     * Verify fetched ddg payment throws an exception when being shipped.
     *
     * @test
     *
     * @depends ddgTypeCharge
     *
     * @param Charge $ddgCharge
     *
     * @throws UnzerApiException
     */
    public function insTypeShouldNotBeShippable(Charge $ddgCharge)
    {
        $invoiceId = 'i' . self::generateRandomId();

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::CORE_ERROR_INSURANCE_ALREADY_ACTIVATED);
        $this->unzer->ship($ddgCharge->getPayment(), $invoiceId);
    }
}
