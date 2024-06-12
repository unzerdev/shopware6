<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and functionality
 * of the Google Pay payment method.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Constants\ExemptionType;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\EmbeddedResources\CardTransactionData;
use UnzerSDK\Resources\EmbeddedResources\GooglePay\IntermediateSigningKey;
use UnzerSDK\Resources\EmbeddedResources\GooglePay\SignedKey;
use UnzerSDK\Resources\EmbeddedResources\GooglePay\SignedMessage;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Googlepay;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\test\Fixtures\JsonProvider;

class GooglePayTest extends BaseIntegrationTest
{
    protected function setUp(): void
    {
        if (empty($this->getTestToken())) {
            $this->markTestSkipped('Skiped by default. To run test locally, overwrite a googlepay payment token
         in `test/Fixtures/jsonData/googlePay/googlepayToken.json`.');
        }
        parent::setUp();
    }

    /**
     * Verify that googlepay payment type resource can be created.
     *
     * @test
     *
     * @return BasePaymentType
     */
    public function googlePayShouldBeCreatable(): BasePaymentType
    {
        $googlePayToken = $this->getTestToken();
        $googlepay = $this->createGooglepayObjectFromToken($googlePayToken);

        $this->unzer->createPaymentType($googlepay);

        $this->assertInstanceOf(Googlepay::class, $googlepay);
        $this->assertNotNull($googlepay->getId());
        $this->assertSame($this->unzer, $googlepay->getUnzerObject());

        $geoLocation = $googlepay->getGeoLocation();
        $this->assertNotEmpty($geoLocation->getClientIp());
        $this->assertNotEmpty($geoLocation->getCountryCode());

        return $googlepay;
    }

    /**
     * Verify that a googlepay resource can be fetched from the api using its id.
     *
     * @test
     *
     * @depends googlePayShouldBeCreatable
     *
     * @param mixed $type
     */
    public function googlepayCanBeFetched($type): void
    {
        $this->assertNotNull($type->getId());

        /** @var Googlepay $fetchedGooglepay */
        $fetchedGooglepay = $this->unzer->fetchPaymentType($type->getId());
        $this->assertNotNull($fetchedGooglepay->getId());
        $this->assertEquals($type->getNumber(), $fetchedGooglepay->getNumber());
        $this->assertEquals($type->getExpiryDate(), $fetchedGooglepay->getExpiryDate());
    }

    /**
     * Verify that authorization can be performed with Google Pay.
     *
     * @test
     *
     * @depends googlePayShouldBeCreatable
     *
     * @param mixed $type
     */
    public function googlepayCanPerformAuthorization($type): Authorization
    {
        $authorizationRequest = $this->getLvpAuthorizationObject();
        $authorization = $this->getUnzerObject()
            ->performAuthorization(
                $authorizationRequest,
                $type
            );

        // verify authorization has been created
        $this->assertNotNull($authorization->getId());

        // verify payment object has been created
        $payment = $authorization->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        // verify resources are linked properly
        $this->assertSame($authorization, $payment->getAuthorization());
        $this->assertSame($type, $payment->getPaymentType());

        // verify the payment object has been updated properly
        $this->assertAmounts($payment, 2.99, 0.0, 2.99, 0.0);
        $this->assertTrue($payment->isPending());
        $this->assertTrue($authorization->isSuccess());

        return $authorization;
    }

    /**
     * Verify that authorization can be charged with Google Pay.
     *
     * @test
     *
     * @depends googlepayCanPerformAuthorization
     *
     * @param Authorization $authorization
     */
    public function authorizationCanBeCharged(Authorization $authorization): Payment
    {
        $charge = $this->getUnzerObject()
            ->performChargeOnPayment(
                $authorization->getPayment(),
                new Charge()
            );

        // verify charge has been created
        $this->assertNotNull($charge->getId());

        // verify payment object has been created
        $payment = $charge->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        // verify resources are linked properly
        $this->assertSame($charge, $payment->getCharge('s-chg-1'));
        $this->assertSame($authorization->getPayment()->getPaymentType(), $payment->getPaymentType());

        // verify the payment object has been updated properly
        $this->assertAmounts($payment, 0, 2.99, 2.99, 0.0);
        $this->assertTrue($payment->isCompleted());
        $this->assertTrue($charge->isSuccess());

        return $payment;
    }

    /**
     * Verify the googlepay can perform charges and creates a payment object doing so.
     *
     * @test
     *
     * @depends googlePayShouldBeCreatable
     *
     * @param mixed $type
     */
    public function canPerformCharge($type): void
    {
        $charge = $this->getUnzerObject()
            ->performCharge(
                $this->getLvpChargeObject(),
                $type
            );

        $fetchedType = $this->unzer->fetchPaymentType($type->getId());

        // verify charge has been created
        $this->assertNotNull($charge->getId());

        // verify payment object has been created
        $payment = $charge->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        // verify resources are linked properly
        $this->assertEquals($charge->expose(), $payment->getCharge($charge->getId())->expose());

        // verify the payment object has been updated properly
        $this->assertAmounts($payment, 0.0, 2.99, 2.99, 0.0);
        $this->assertTrue($payment->isCompleted());
    }

    /**
     * Verify the googlepay can charge part of the authorized amount and the payment state is updated accordingly.
     *
     * @test
     */
    public function partialChargeAfterAuthorization(): void
    {
        $googlepay          = $this->createGooglepayObjectFromToken($this->getTestToken());
        /** @var Googlepay $googlepay */
        $googlepay          = $this->unzer->createPaymentType($googlepay);
        $authorization = $this->getUnzerObject()
            ->performAuthorization(
                $this->getLvpAuthorizationObject(),
                $googlepay
            );

        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 2.99, 0.0, 2.99, 0.0);
        $this->assertTrue($payment->isPending());

        $charge   = $this->unzer->performChargeOnPayment($payment->getId(), new Charge(1));
        $payment1 = $charge->getPayment();
        $this->assertAmounts($payment1, 1.99, 1, 2.99, 0.0);
        $this->assertTrue($payment1->isPartlyPaid());

        $charge   = $this->unzer->performChargeOnPayment($payment->getId(), new Charge(1));
        $payment2 = $charge->getPayment();
        $this->assertAmounts($payment2, 0.99, 2, 2.99, 0.0);
        $this->assertTrue($payment2->isPartlyPaid());

        $charge   = $this->unzer->performChargeOnPayment($payment->getId(), new Charge(0.99));
        $payment3 = $charge->getPayment();
        $this->assertAmounts($payment3, 00.0, 2.99, 2.99, 0.0);
        $this->assertTrue($payment3->isCompleted());
    }

    /**
     * Verify that an exception is thrown when trying to charge more than authorized.
     *
     * @test
     */
    public function exceptionShouldBeThrownWhenChargingMoreThenAuthorized(): void
    {
        $googlepay          = $this->createGooglepayObjectFromToken($this->getTestToken());
        /** @var Googlepay $googlepay */
        $googlepay          = $this->unzer->createPaymentType($googlepay);
        $authorization = $this->getUnzerObject()
            ->performAuthorization(
                $this->getLvpAuthorizationObject(),
                $googlepay
            );
        $payment       = $authorization->getPayment();
        $this->assertAmounts($payment, 2.99, 0.0, 2.99, 0.0);
        $this->assertTrue($payment->isPending());

        $charge   = $this->unzer->performChargeOnPayment($payment->getId(), new Charge(1.99));
        $payment1 = $charge->getPayment();
        $this->assertAmounts($payment1, 1, 1.99, 2.99, 0.0);
        $this->assertTrue($payment1->isPartlyPaid());

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_CHARGED_AMOUNT_HIGHER_THAN_EXPECTED);
        $this->unzer->performChargeOnPayment($payment->getId(), new Charge(2));
    }

    /**
     * @test
     */
    public function fullCancelAfterCharge(): void
    {
        $googlepay    = $this->createGooglepayObjectFromToken($this->getTestToken());
        /** @var Googlepay $googlepay */
        $googlepay    = $this->unzer->createPaymentType($googlepay);
        $charge = $this->getUnzerObject()
            ->performCharge(
                $this->getLvpChargeObject(),
                $googlepay
            );
        $payment = $charge->getPayment();

        $this->assertAmounts($payment, 0.0, 2.99, 2.99, 0.0);
        $this->assertTrue($payment->isCompleted());

        $this->unzer->cancelChargedPayment($payment);
        $this->assertAmounts($payment, 0.0, 0.0, 2.99, 2.99);
        $this->assertTrue($payment->isCanceled());
    }

    /** Creates an authorization request object with low value payment(lvp) set to avoid 3ds challenge.
     *
     * @return Authorization
     */
    protected function getLvpAuthorizationObject()
    {
        return (new Authorization(2.99, 'EUR', self::RETURN_URL))
            ->setCardTransactionData(
                (new CardTransactionData())->setExemptionType(ExemptionType::LOW_VALUE_PAYMENT)
            );
    }

    /** Creates an charge request object with low value payment(lvp) set to avoid 3ds challenge.
     *
     * @return Charge
     */
    protected function getLvpChargeObject()
    {
        return (new Charge(2.99, 'EUR', self::RETURN_URL))
            ->setCardTransactionData(
                (new CardTransactionData())->setExemptionType(ExemptionType::LOW_VALUE_PAYMENT)
            );
    }

    /**
     * @param $googlePayToken
     *
     * @return Googlepay
     */
    protected function createGooglepayObjectFromToken($googlePayToken): Googlepay
    {
        $googlepay = (new Googlepay());
        $this->assertNull($googlepay->getId());

        $geoLocation = $googlepay->getGeoLocation();
        $this->assertNull($geoLocation->getClientIp());
        $this->assertNull($geoLocation->getCountryCode());

        $signedKey = (new SignedKey())
            ->setKeyValue($googlePayToken->intermediateSigningKey->signedKey->keyValue)
            ->setKeyExpiration($googlePayToken->intermediateSigningKey->signedKey->keyExpiration);

        $intermediateSigningKey = (new IntermediateSigningKey())
            ->setSignedKey($signedKey)
            ->setSignatures($googlePayToken->intermediateSigningKey->signatures);

        $protocolVersion = $googlePayToken->protocolVersion;
        $encryptedMessage = $googlePayToken->signedMessage->encryptedMessage;
        $ephemeralPublicKey = $googlePayToken->signedMessage->ephemeralPublicKey;
        $tag = $googlePayToken->signedMessage->tag;

        $signedMessage = (new SignedMessage())
            ->setEncryptedMessage($encryptedMessage)
            ->setEphemeralPublicKey($ephemeralPublicKey)
            ->setTag($tag);

        $googlepay->setSignature($googlePayToken->signature)
            ->setIntermediateSigningKey($intermediateSigningKey)
            ->setProtocolVersion($protocolVersion)
            ->setSignedMessage($signedMessage);

        return $googlepay;
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    protected function getTestToken()
    {
        $googlePayToken = json_decode(JsonProvider::getJsonFromFile('googlepay/googlepayToken.json'), false);
        if (!empty($googlePayToken)) {
            $googlePayToken->intermediateSigningKey->signedKey = json_decode($googlePayToken->intermediateSigningKey->signedKey);
            $googlePayToken->signedMessage = json_decode($googlePayToken->signedMessage);
        }
        return $googlePayToken;
    }
}
