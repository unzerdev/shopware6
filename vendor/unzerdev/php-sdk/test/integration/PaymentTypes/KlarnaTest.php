<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and
 * functionality of the payment method klarna.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\Klarna;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;

class KlarnaTest extends BaseIntegrationTest
{
    /**
     * Verify klarna can be created.
     *
     * @test
     *
     * @return Klarna
     */
    public function klarnaShouldBeCreatableAndFetchable(): Klarna
    {
        $klarna = $this->unzer->createPaymentType(new Klarna());
        $this->assertInstanceOf(Klarna::class, $klarna);
        $this->assertNotNull($klarna->getId());

        /** @var Klarna $fetchedKlarna */
        $fetchedKlarna = $this->unzer->fetchPaymentType($klarna->getId());
        $this->assertInstanceOf(Klarna::class, $fetchedKlarna);
        $this->assertEquals($klarna->expose(), $fetchedKlarna->expose());
        $this->assertNotEmpty($fetchedKlarna->getGeoLocation()->getClientIp());

        return $fetchedKlarna;
    }

    /**
     * Verify klarna is not authorizable.
     *
     * @test
     *
     * @param Klarna $klarna
     *
     * @depends klarnaShouldBeCreatableAndFetchable
     */
    public function klarnaShouldBeAuthorizable(Klarna $klarna): void
    {
        $authorizationInstance = (new Authorization(99.99, 'EUR', self::RETURN_URL))
            ->setTermsAndConditionUrl('https://www.unzer.com/de')
            ->setPrivacyPolicyUrl('https://www.unzer.com/de');

        $customer = $this->getMaximumCustomerInclShippingAddress();
        $customer->setLanguage('de');

        $basket = $this->createV2Basket();
        $authorization = $this->unzer->performAuthorization($authorizationInstance, $klarna, $customer, null, $basket);
        $this->assertNotNull($authorization);
        $this->assertNotEmpty($authorization->getId());
        $this->assertNotEmpty($authorization->getRedirectUrl());
    }

    /**
     * Verify klarna is not directly chargeable.
     *
     * @test
     *
     * @param Klarna $klarna
     *
     * @depends klarnaShouldBeCreatableAndFetchable
     */
    public function klarnaShouldNotBeDirectlyChargable(Klarna $klarna)
    {
        $chargeInstance = (new Charge(99.99, 'EUR', self::RETURN_URL))
            ->setTermsAndConditionUrl('https://www.unzer.com/de')
            ->setPrivacyPolicyUrl('https://www.unzer.com/de');

        $customer = $this->getMaximumCustomerInclShippingAddress();
        $customer->setLanguage('de');

        $basket = $this->createV2Basket();

        $this->expectException(UnzerApiException::class);
        $this->unzer->performCharge($chargeInstance, $klarna, $customer, null, $basket);
    }
}
