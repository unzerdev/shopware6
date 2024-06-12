<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and functionality of the payment method paypal.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;

class PaypalTest extends BaseIntegrationTest
{
    /**
     * Verify PayPal payment type can be created and fetched.
     *
     * @test
     *
     * @return BasePaymentType
     */
    public function paypalShouldBeCreatableAndFetchable(): BasePaymentType
    {
        $paypal = $this->unzer->createPaymentType(new Paypal());
        $this->assertInstanceOf(Paypal::class, $paypal);
        $this->assertNotEmpty($paypal->getId());

        $fetchedPaypal = $this->unzer->fetchPaymentType($paypal->getId());
        $this->assertInstanceOf(Paypal::class, $fetchedPaypal);
        $this->assertNotSame($paypal, $fetchedPaypal);
        $this->assertEquals($paypal->expose(), $fetchedPaypal->expose());

        return $fetchedPaypal;
    }

    /**
     * Verify PayPal payment type can be created and fetched with email.
     *
     * @test
     *
     * @return BasePaymentType
     */
    public function paypalShouldBeCreatableAndFetchableWithEmail(): BasePaymentType
    {
        $paypal = (new Paypal())->setEmail('max@mustermann.de');
        $this->unzer->createPaymentType($paypal);
        $this->assertNotEmpty($paypal->getId());

        $fetchedPaypal = $this->unzer->fetchPaymentType($paypal->getId());
        $this->assertInstanceOf(Paypal::class, $fetchedPaypal);
        $this->assertNotSame($paypal, $fetchedPaypal);
        $this->assertEquals($paypal->expose(), $fetchedPaypal->expose());

        return $fetchedPaypal;
    }

    /**
     * Verify paypal can authorize.
     *
     * @test
     *
     * @depends paypalShouldBeCreatableAndFetchable
     *
     * @param Paypal $paypal
     */
    public function paypalShouldBeAuthorizable(Paypal $paypal): void
    {
        $authorization = $paypal->authorize(100.0, 'EUR', self::RETURN_URL);
        $this->assertNotNull($authorization);
        $this->assertNotEmpty($authorization->getId());
        $this->assertNotEmpty($authorization->getRedirectUrl());

        $payment = $authorization->getPayment();
        $this->assertNotNull($payment);
        $this->assertTrue($payment->isPending());
    }

    /**
     * Verify paypal can charge.
     *
     * @test
     *
     * @depends paypalShouldBeCreatableAndFetchable
     *
     * @param Paypal $paypal
     */
    public function paypalShouldBeChargeable(Paypal $paypal): void
    {
        $charge = $paypal->charge(100.0, 'EUR', self::RETURN_URL);
        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
    }

    /**
     * Test PayPal Express checkout.
     *
     * @test
     *
     * @depends paypalShouldBeCreatableAndFetchable
     *
     * @param Paypal $paypal
     *
     * @return Charge
     *
     * @throws UnzerApiException
     */
    public function paypalChargeWithExpressCheckout(Paypal $paypal): Charge
    {
        $initialAmount = 100.00;
        $charge = new Charge($initialAmount, 'EUR', self::RETURN_URL);
        $charge->setCheckoutType('express', $paypal);

        $basketItem = (new BasketItem())
            ->setTitle('ItemTitle')
            ->setAmountPerUnitGross($initialAmount);
        $basket = (new Basket())->setTotalValueGross($initialAmount);
        $basket->addBasketItem($basketItem);
        $this->getUnzerObject()->performCharge($charge, $paypal, null, null, $basket);
        $this->assertNotEmpty($charge->getId());

        $this->assertTrue($charge->isPending());

        return $charge;
    }

    /**
     * Verify Charge can be updated
     *
     * @test
     *
     * @depends paypalChargeWithExpressCheckout
     */
    public function updateChargeThrowsExceptionWhenStatusIsPending(Charge $charge): void
    {
        $charge->setAmount(120);
        $this->expectException(UnzerApiException::class);

        $this->getUnzerObject()->updateCharge($charge->getPaymentId(), $charge);
    }

    /**
     * Test PayPal Express checkout.
     *
     * @test
     *
     * @depends paypalShouldBeCreatableAndFetchable
     *
     * @param Paypal $paypal
     *
     * @return Authorization
     *
     * @throws UnzerApiException
     */
    public function paypalAuthorizeWithExpressCheckout(Paypal $paypal): Authorization
    {
        $initialAmount = 100.00;
        $authorize = new Authorization($initialAmount, 'EUR', self::RETURN_URL);
        $authorize->setCheckoutType('express', $paypal->getId());

        $basketItem = (new BasketItem())
            ->setTitle('ItemTitle')
            ->setAmountPerUnitGross($initialAmount);
        $basket = (new Basket())->setTotalValueGross($initialAmount);
        $basket->addBasketItem($basketItem);

        $this->getUnzerObject()->performAuthorization($authorize, $paypal, null, null, $basket);
        $this->assertNotEmpty($authorize->getId());

        $this->assertTrue($authorize->isPending());
        $this->assertNotNull($authorize->getCheckoutType());

        return $authorize;
    }

    /**
     * Test PayPal Express checkout.
     *
     * @test
     *
     * @depends paypalShouldBeCreatableAndFetchable
     *
     * @param Paypal $paypal
     *
     * @return Authorization
     *
     * @throws UnzerApiException
     */
    public function invalidCheckoutTypeThrowsApiException(Paypal $paypal): Authorization
    {
        $authorize = new Authorization(100.00, 'EUR', self::RETURN_URL);
        $authorize->setCheckoutType('expresso', $paypal);

        $this->expectException(UnzerApiException::class);

        $this->getUnzerObject()->performAuthorization($authorize, $paypal);

        return $authorize;
    }

    /**
     * Verify Authorize can be updated
     *
     * @test
     *
     * @depends paypalAuthorizeWithExpressCheckout
     */
    public function updateAuthorizeThrowsApiExceptionWhenStatusIsPending(Authorization $authorize): void
    {
        $authorize->setAmount(120);
        $this->expectException(UnzerApiException::class);
        $this->getUnzerObject()->updateAuthorization($authorize->getPaymentId(), $authorize);
    }
}
