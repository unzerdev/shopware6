<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and functionality
 * of the payment method Przelewy24.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Przelewy24;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\test\Helper\TestEnvironmentService;

class Przelewy24Test extends BaseIntegrationTest
{
    /**
     * Verify Przelewy24 payment type can be created and fetched.
     *
     * @test
     *
     * @return BasePaymentType
     */
    public function przelewy24ShouldBeCreatableAndFetchable(): BasePaymentType
    {
        $przelewy24 = $this->unzer->createPaymentType(new Przelewy24());
        $this->assertInstanceOf(Przelewy24::class, $przelewy24);
        $this->assertNotEmpty($przelewy24->getId());

        $fetchedPrzelewy24 = $this->unzer->fetchPaymentType($przelewy24->getId());
        $this->assertInstanceOf(Przelewy24::class, $fetchedPrzelewy24);
        $this->assertNotSame($przelewy24, $fetchedPrzelewy24);
        $this->assertEquals($przelewy24->expose(), $fetchedPrzelewy24->expose());

        return $fetchedPrzelewy24;
    }

    /**
     * Verify przelewy24 can authorize.
     *
     * @test
     *
     * @depends przelewy24ShouldBeCreatableAndFetchable
     *
     * @param Przelewy24 $przelewy24
     */
    public function przelewy24ShouldBeChargeable(Przelewy24 $przelewy24): void
    {
        $charge = new Charge(100.0, 'PLN', self::RETURN_URL);
        $customer = $this->getMaximumCustomer();
        $customer->getShippingAddress()
            ->setCountry('PL');
        $customer->getBillingAddress()
            ->setCountry('PL');
        $this->getUnzerObject()->performCharge($charge, $przelewy24, $customer);
        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
        $this->assertNotEmpty($charge->getRedirectUrl());

        $payment = $charge->getPayment();
        $this->assertNotNull($payment);
        $this->assertTrue($payment->isPending());
    }

    /**
     * Verify przelewy24 can not be authorized.
     *
     * @test
     *
     * @depends przelewy24ShouldBeCreatableAndFetchable
     *
     * @param Przelewy24 $przelewy24
     */
    public function przelewy24ShouldNotBeAuthorizable(Przelewy24 $przelewy24): void
    {
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED);

        $this->unzer->authorize(100.0, 'PLN', $przelewy24, self::RETURN_URL);
    }

    /**
     * Verify przelewy24 can only handle Currency::POLISH_ZLOTY.
     *
     * @test
     *
     * @dataProvider przelewy24CurrencyCodeProvider
     *
     * @param string $currencyCode
     */
    public function przelewy24ShouldThrowExceptionIfCurrencyIsNotSupported($currencyCode): void
    {
        /** @var Przelewy24 $przelewy24 */
        $przelewy24 = $this->unzer->createPaymentType(new Przelewy24());
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_CURRENCY_IS_NOT_SUPPORTED);
        $charge = new Charge(100.0, $currencyCode, self::RETURN_URL);
        $customer = $this->getMaximumCustomer();
        $customer->getShippingAddress()
            ->setCountry('PL');
        $customer->getBillingAddress()
            ->setCountry('PL');
        $this->getUnzerObject()->performCharge($charge, $przelewy24, $customer);
    }

    /**
     * Verify przelewy24 can only handle Currency::POLISH_ZLOTY.
     *
     * @test
     *
     * @dataProvider legazyPrzelewy24CurrencyCodeProvider
     *
     * @param string $currencyCode
     */
    public function legazyConfigPrzelewy24ShouldThrowExceptionIfCurrencyIsNotSupported($currencyCode): void
    {
        $this->getUnzerObject()->getUnzerObject()->setKey(TestEnvironmentService::getLegacyTestPrivateKey());
        /** @var Przelewy24 $przelewy24 */
        $przelewy24 = $this->unzer->createPaymentType(new Przelewy24());
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_CURRENCY_IS_NOT_SUPPORTED);
        $przelewy24->charge(100.0, $currencyCode, self::RETURN_URL);
    }

    //<editor-fold desc="Data Providers">

    /**
     * Provides a subset of currencies not allowed by this payment method.
     */
    public function przelewy24CurrencyCodeProvider(): array
    {
        return [
            'US Dollar' => ['USD'],
            'Swiss Franc' => ['CHF']
        ];
    }

    /**
     * Provides a subset of currencies not allowed by this payment method.
     */
    public function legazyPrzelewy24CurrencyCodeProvider(): array
    {
        return [
            'EUR' => ['EUR'],
            'US Dollar' => ['USD'],
            'Swiss Franc' => ['CHF']
        ];
    }

    //</editor-fold>
}
