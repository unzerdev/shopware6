<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and
 * functionality of the payment method Twint.
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Twint;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;

class TwintTest extends BaseIntegrationTest
{
    protected const testClass = Twint::class;

    /**
     * Verify twint can be created.
     *
     * @test
     */
    public function typeShouldBeCreatableAndFetchable(): BasePaymentType
    {
        $paymentType = $this->getUnzerObject()->createPaymentType($this->createTypeInstance());
        $this->assertInstanceOf(self::testClass, $paymentType);
        $this->assertNotNull($paymentType->getId());

        /** @var Twint $fetchedTwint */
        $fetchedTwint = $this->unzer->fetchPaymentType($paymentType->getId());
        $this->assertInstanceOf(self::testClass, $fetchedTwint);
        $this->assertEquals($paymentType->expose(), $fetchedTwint->expose());
        $this->assertNotEmpty($fetchedTwint->getGeoLocation()->getClientIp());

        return $fetchedTwint;
    }

    /**
     * Verify twint is chargeable.
     *
     * @test
     *
     * @depends typeShouldBeCreatableAndFetchable
     */
    public function twintShouldBeAbleToCharge(BasePaymentType $paymentType): Charge
    {
        $charge = $this->unzer->charge(100.0, 'CHF', $paymentType, self::RETURN_URL);
        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
        $this->assertNotEmpty($charge->getRedirectUrl());

        return $charge;
    }

    /**
     * Verify twint is not authorizable.
     *
     * @test
     *
     * @depends typeShouldBeCreatableAndFetchable
     */
    public function twintShouldNotBeAuthorizable(BasePaymentType $paymentType): void
    {
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED);

        $this->unzer->authorize(100.0, 'EUR', $paymentType, self::RETURN_URL);
    }

    public function createTypeInstance(): BasePaymentType
    {
        $class = self::testClass;
        return new $class();
    }
}
