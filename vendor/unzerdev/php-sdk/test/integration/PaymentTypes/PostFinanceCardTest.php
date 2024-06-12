<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and
 * functionality of the payment method PostFinanceCardTest.
 *
 * @link     https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\PostFinanceCard;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;

class PostFinanceCardTest extends BaseIntegrationTest
{
    /**
     * Verify PostFinanceCard can be created.
     *
     * @test
     *
     * @return PostFinanceCard
     */
    public function postFinanceCardShouldBeCreatableAndFetchable(): PostFinanceCard
    {
        $paymentType = $this->unzer->createPaymentType(new PostFinanceCard());
        $this->assertInstanceOf(PostFinanceCard::class, $paymentType);
        $this->assertNotNull($paymentType->getId());

        /** @var PostFinanceCard $fetchedType */
        $fetchedType = $this->unzer->fetchPaymentType($paymentType->getId());
        $this->assertInstanceOf(PostFinanceCard::class, $fetchedType);
        $this->assertEquals($paymentType->expose(), $fetchedType->expose());
        $this->assertNotEmpty($fetchedType->getGeoLocation()->getClientIp());

        return $fetchedType;
    }

    /**
     * Verify PostFinanceCard is chargeable.
     *
     * @test
     *
     * @param PostFinanceCard $paymentType
     *
     * @return Charge
     *
     * @depends postFinanceCardShouldBeCreatableAndFetchable
     */
    public function postFinanceCardShouldBeAbleToCharge(PostFinanceCard $paymentType): Charge
    {
        $charge = new Charge(100.0, 'CHF', self::RETURN_URL);
        $this->getUnzerObject()->performCharge($charge, $paymentType);

        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
        $this->assertNotEmpty($charge->getRedirectUrl());

        return $charge;
    }

    /**
     * Verify postFinanceCard is not authorizable.
     *
     * @test
     *
     * @param PostFinanceCard $postFinanceCard
     *
     * @depends postFinanceCardShouldBeCreatableAndFetchable
     */
    public function postFinanceCardShouldNotBeAuthorizable(PostFinanceCard $postFinanceCard): void
    {
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED);

        $authorization = new Authorization(100.0, 'CHF', self::RETURN_URL);
        $this->unzer->performAuthorization($authorization, $postFinanceCard);
    }
}
