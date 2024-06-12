<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and
 * functionality of the payment method Wechatpay.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\Wechatpay;
use UnzerSDK\test\BaseIntegrationTest;

class WechatpayTest extends BaseIntegrationTest
{
    /**
     * Verify wechatpay can be created.
     *
     * @test
     */
    public function wechatpayShouldBeCreatableAndFetchable(): void
    {
        $wechatpay = $this->unzer->createPaymentType(new Wechatpay());
        $this->assertInstanceOf(Wechatpay::class, $wechatpay);
        $this->assertNotNull($wechatpay->getId());

        /** @var Wechatpay $fetchedWechatpay */
        $fetchedWechatpay = $this->unzer->fetchPaymentType($wechatpay->getId());
        $this->assertInstanceOf(Wechatpay::class, $fetchedWechatpay);
        $this->assertEquals($wechatpay->expose(), $fetchedWechatpay->expose());
    }

    /**
     * Verify wechatpay is chargeable.
     *
     * @test
     */
    public function wechatpayShouldBeAbleToCharge(): void
    {
        /** @var Wechatpay $wechatpay */
        $wechatpay = $this->unzer->createPaymentType(new Wechatpay());
        $charge = $wechatpay->charge(100.0, 'EUR', self::RETURN_URL);
        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
        $this->assertNotEmpty($charge->getRedirectUrl());
    }

    /**
     * Verify wechatpay is not authorizable.
     *
     * @test
     */
    public function wechatpayShouldNotBeAuthorizable(): void
    {
        $wechatpay = $this->unzer->createPaymentType(new Wechatpay());
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED);

        $this->unzer->authorize(100.0, 'EUR', $wechatpay, self::RETURN_URL);
    }
}
