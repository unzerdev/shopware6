<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and
 * functionality of the payment method PostFinanceEfinanceTest.
 *
 * @link     https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\PostFinanceEfinance;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;

class PostFinanceEfinanceTest extends BaseIntegrationTest
{
    /**
     * Verify PostFinanceEfinance can be created.
     *
     * @test
     *
     * @return PostFinanceEfinance
     */
    public function postFinanceEfinanceShouldBeCreatableAndFetchable(): PostFinanceEfinance
    {
        $paymentType = $this->unzer->createPaymentType(new PostFinanceEfinance());
        $this->assertInstanceOf(PostFinanceEfinance::class, $paymentType);
        $this->assertNotNull($paymentType->getId());

        /** @var PostFinanceEfinance $fetchedType */
        $fetchedType = $this->unzer->fetchPaymentType($paymentType->getId());
        $this->assertInstanceOf(PostFinanceEfinance::class, $fetchedType);
        $this->assertEquals($paymentType->expose(), $fetchedType->expose());
        $this->assertNotEmpty($fetchedType->getGeoLocation()->getClientIp());

        return $fetchedType;
    }

    /**
     * Verify PostFinanceEfinance is chargeable.
     *
     * @test
     *
     * @param PostFinanceEfinance $paymentType
     *
     * @return Charge
     *
     * @depends postFinanceEfinanceShouldBeCreatableAndFetchable
     */
    public function postFinanceEfinanceShouldBeAbleToCharge(PostFinanceEfinance $paymentType): Charge
    {
        $charge = new Charge(100.0, 'CHF', self::RETURN_URL);
        $this->getUnzerObject()->performCharge($charge, $paymentType);

        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
        $this->assertNotEmpty($charge->getRedirectUrl());

        return $charge;
    }

    /**
     * Verify PostFinanceEfinance is not authorizable.
     *
     * @test
     *
     * @param PostFinanceEfinance $postFinanceEfinance
     *
     * @depends postFinanceEfinanceShouldBeCreatableAndFetchable
     */
    public function postFinanceEfinanceShouldNotBeAuthorizable(PostFinanceEfinance $postFinanceEfinance): void
    {
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED);

        $authorization = new Authorization(100.0, 'CHF', self::RETURN_URL);
        $this->unzer->performAuthorization($authorization, $postFinanceEfinance);
    }
}
