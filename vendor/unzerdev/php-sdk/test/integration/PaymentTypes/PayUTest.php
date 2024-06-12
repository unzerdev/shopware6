<?php

/**
 * This class defines integration tests to verify interface and
 * functionality of the payment method PayUTest.
 *
 * @link     https://docs.unzer.com/
 *
 */

/** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpDocMissingThrowsInspection */

namespace PaymentTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\PayU;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;

class PayUTest extends BaseIntegrationTest
{
    /**
     * Verify PayU can be created.
     *
     * @test
     *
     * @return PayU
     */
    public function payUShouldBeCreatableAndFetchable(): PayU
    {
        $paymentType = $this->unzer->createPaymentType(new PayU());
        $this->assertInstanceOf(PayU::class, $paymentType);
        $this->assertNotNull($paymentType->getId());

        /** @var PayU $fetchedType */
        $fetchedType = $this->unzer->fetchPaymentType($paymentType->getId());
        $this->assertInstanceOf(PayU::class, $fetchedType);
        $this->assertEquals($paymentType->expose(), $fetchedType->expose());
        $this->assertNotEmpty($fetchedType->getGeoLocation()->getClientIp());

        return $fetchedType;
    }

    /**
     * Verify PayU is chargeable.
     *
     * @test
     *
     * @param PayU  $paymentType
     * @param mixed $currency
     *
     * @return Charge
     *
     * @dataProvider supportedCurrencies
     *
     * @depends      payUShouldBeCreatableAndFetchable
     */
    public function payUShouldBeAbleToCharge($currency, PayU $paymentType): Charge
    {
        $charge = new Charge(100.0, $currency, self::RETURN_URL);
        $this->getUnzerObject()->performCharge($charge, $paymentType);

        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
        $this->assertNotEmpty($charge->getRedirectUrl());

        return $charge;
    }

    /**
     * Verify payU is not authorizable.
     *
     * @test
     *
     * @param PayU $payU
     *
     * @depends payUShouldBeCreatableAndFetchable
     */
    public function payUShouldNotBeAuthorizable(PayU $payU): void
    {
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED);

        $authorization = new Authorization(100.0, 'CHF', self::RETURN_URL);
        $this->unzer->performAuthorization($authorization, $payU);
    }

    public function supportedCurrencies(): array
    {
        return [
            'PLN' => ['PLN'],
            'CZK' => ['CZK']
        ];
    }
}
