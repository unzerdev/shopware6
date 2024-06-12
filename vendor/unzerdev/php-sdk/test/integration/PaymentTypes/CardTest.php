<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and functionality
 * of the card payment methods e.g. Credit Card and Debit Card.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Constants\ExemptionType;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\EmbeddedResources\CardTransactionData;
use UnzerSDK\Resources\EmbeddedResources\CardDetails;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Services\ValueService;
use UnzerSDK\test\BaseIntegrationTest;

class CardTest extends BaseIntegrationTest
{
    //<editor-fold desc="Tests">

    /**
     * Verify that card payment type resource can be created.
     *
     * @test
     *
     * @dataProvider cardShouldBeCreatableDP
     *
     * @param string      $cardNumber
     * @param CardDetails $expectedCardDetails
     *
     * @return BasePaymentType
     */
    public function cardShouldBeCreatable(string $cardNumber, CardDetails $expectedCardDetails): BasePaymentType
    {
        $card = $this->createCardObject($cardNumber);
        $this->assertNull($card->getId());

        $geoLocation = $card->getGeoLocation();
        $this->assertNull($geoLocation->getClientIp());
        $this->assertNull($geoLocation->getCountryCode());

        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);

        $this->assertInstanceOf(Card::class, $card);
        $this->assertNotNull($card->getId());
        $this->assertSame($this->unzer, $card->getUnzerObject());
        $this->assertEquals($expectedCardDetails, $card->getCardDetails());

        $geoLocation = $card->getGeoLocation();
        $this->assertNotEmpty($geoLocation->getClientIp());
        $this->assertNotEmpty($geoLocation->getCountryCode());

        return $card;
    }

    /**
     * Verify card can be created with email address.
     *
     * @dataProvider cardShouldUseEmailDataProvider
     *
     * @test
     *
     * @param mixed $email
     * @param mixed $expected
     */
    public function cardShouldUseEmail($email, $expected)
    {
        // when.
        $card = $this->createCardObject('4711100000000000');
        $this->assertNull($card->getId());
        $this->assertNull($card->getEmail()); // default value is set.

        // then.
        $card->setEmail($email); // override email.

        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);
        $this->assertNotNull($card->getId());
        $this->assertEquals($email, $card->getEmail());

        // ensure the fetched card object contains the email address.
        $fetchedCard = $this->unzer->fetchPaymentType($card->getId());
        $this->assertEquals($expected, $fetchedCard->getEmail());
        $card->charge(119.00, 'EUR', 'https://unzer.com');
    }

    /**
     * Card should be chargeable with recurrence type.
     *
     * @test
     *
     * @dataProvider supportedRecurrenceTypesDP
     *
     * @param       $recurrenceType
     * @param mixed $isrecurring
     *
     * @throws UnzerApiException
     */
    public function cardShouldBeChargeableWithRecurrenceType($recurrenceType, $isrecurring): void
    {
        $this->useNon3dsKey();
        $card = $this->createCardObject('4711100000000000');
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);
        $this->assertNotTrue($card->isRecurring());

        $chargeResponse = $card->charge('99.99', 'EUR', 'https://unzer.com', null, null, null, null, null, null, null, $recurrenceType);
        $this->assertEquals($recurrenceType, $chargeResponse->getRecurrenceType());
        $fetchedCharge = $this->unzer->fetchChargeById($chargeResponse->getPaymentId(), $chargeResponse->getId());

        if ($recurrenceType !== null) {
            $this->assertNotNull($fetchedCharge->getAdditionalTransactionData());
        }
        $this->assertEquals($recurrenceType, $fetchedCharge->getRecurrenceType());

        $fetchedCard = $this->getUnzerObject()->fetchPaymentType($card->getId());
        $this->assertTrue($fetchedCard->isRecurring());
    }

    /**
     * Invalid recurrence type should throw API exception.
     *
     * @test
     *
     * @throws UnzerApiException
     *
     * @dataProvider invalidRecurrenceTypesDP
     *
     * @param mixed $recurrenceType
     */
    public function invalidRecurrenceTypeShouldThrowApiException($recurrenceType): void
    {
        $card = $this->createCardObject('4711100000000000');
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);
        $this->expectException(UnzerApiException::class);
        $card->charge('99.99', 'EUR', 'https://unzer.com', null, null, null, null, null, null, null, $recurrenceType);
    }

    /**
     * Invalid expiry date should throw API exception.
     *
     * @test
     */
    public function invalidExpiryDateShouldThrowApiException(): void
    {
        $card = $this->createCardObject('4711100000000000');
        $card->setExpiryDate('01/2001');

        /** @var Card $card */
        $this->expectException(UnzerApiException::class);
        $this->unzer->createPaymentType($card);
    }

    /**
     * Card should be chargeable with recurrence type.
     *
     * @test
     *
     * @dataProvider supportedRecurrenceTypesDP
     *
     * @param $recurrenceType
     *
     * @throws UnzerApiException
     */
    public function cardCanBeAuthorizedWithRecurrenceType($recurrenceType): void
    {
        $card = $this->createCardObject('4711100000000000');
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);
        $chargeResponse = $card->authorize('99.99', 'EUR', 'https://unzer.com', null, null, null, null, null, null, null, $recurrenceType);
        $this->assertEquals($recurrenceType, $chargeResponse->getRecurrenceType());
        $fetchedCharge = $this->unzer->fetchAuthorization($chargeResponse->getPayment());

        if ($recurrenceType !== null) {
            $this->assertNotNull($fetchedCharge->getAdditionalTransactionData());
        }
        $this->assertEquals($recurrenceType, $fetchedCharge->getRecurrenceType());
    }

    /**
     * Verify that an invalid email cause an UnzerApiException.
     *
     * @test
     */
    public function invalidEmailCauseException()
    {
        $card = $this->createCardObject('4711100000000000');
        $this->assertNull($card->getId());
        $this->assertNull($card->getEmail());

        $card->setEmail('invalid-email-address'); // override email.

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionMessage('Email has invalid format.');
        $this->unzer->createPaymentType($card);
    }

    /**
     * Verify that email field can be updated.
     *
     * @test
     */
    public function cardCanBeUpdatedWithEmail()
    {
        $card = $this->createCardObject('5453010000059543');
        $this->assertNull($card->getId());
        $this->assertNull($card->getEmail());

        // when
        $card->setEmail('test@test.com');
        $this->unzer->createPaymentType($card);
        /** @var Card $fetchedCard */
        $fetchedCard = $this->unzer->fetchPaymentType($card->getId());
        // then
        $this->assertEquals('test@test.com', $fetchedCard->getEmail());

        // when
        $fetchedCard->setNumber('4711100000000000')
            ->setEmail('test2@test.com')
            ->setCvc('123');

        $this->unzer->updatePaymentType($fetchedCard);

        // then
        /** @var Card $updatedCard */
        $updatedCard = $this->unzer->fetchPaymentType($fetchedCard->getId());
        $this->assertMatchesRegularExpression('/0000$/', $updatedCard->getNumber());
        $this->assertEquals('test2@test.com', $updatedCard->getEmail());
    }

    /**
     * Verify card creation with 3ds flag set will provide the flag in transactions.
     *
     * @test
     */
    public function cardWith3dsFlagShouldSetItAlsoInTransactions(): void
    {
        $card = $this->createCardObject()->set3ds(false);
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);
        $this->assertFalse($card->get3ds());

        $charge = $card->charge(12.34, 'EUR', 'https://docs.unzer.com');
        $this->assertFalse($charge->isCard3ds());
    }

    /**
     * Verfify card transaction can be used with exemptionType
     *
     * @test
     */
    public function cardTransactionAcceptsExemptionType(): void
    {
        $card = $this->createCardObject();
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);
        $charge = new Charge(12.34, 'EUR', 'https://docs.unzer.com');
        $cardTransactionData = (new CardTransactionData())
            ->setExemptionType(ExemptionType::LOW_VALUE_PAYMENT);

        $charge->setCardTransactionData($cardTransactionData);
        $this->getUnzerObject()->performCharge($charge, $card);

        // Verify lvp value gets mapped from response
        $fetchedCharge = $this->unzer->fetchChargeById($charge->getPaymentId(), $charge->getId());
        $this->assertEquals(ExemptionType::LOW_VALUE_PAYMENT, $fetchedCharge->getCardTransactionData()->getExemptionType());
    }

    /**
     * Verify card transaction returns Liability Shift Indicator.
     *
     * @test
     *
     * @dataProvider cardTransactionReturnsLiabilityIndicatorDP()
     *
     * @param mixed $pan
     */
    public function cardTransactionReturnsLiabilityIndicator($pan): void
    {
        $this->markTestSkipped('Requires a special config for card.');
        $card = $this->createCardObject()->setNumber($pan)->set3ds(false);
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);
        $charge = $card->charge(12.34, 'EUR', 'https://docs.unzer.com');

        // Verify Liability Indicator in response.
        $this->assertNotNull($charge->getAdditionalTransactionData());
        $this->assertNotNull($charge->getAdditionalTransactionData()->card->liability);

        // Verify Liability Indicator In payment response.
        $fetchedPayment = $this->unzer->fetchPayment($charge->getPaymentId());
        $paymentCharge = $fetchedPayment->getCharge('s-chg-1', true);
        $this->assertNotNull($paymentCharge->getAdditionalTransactionData()->card->liability);

        $this->getUnzerObject()->fetchCharge($charge);
    }

    /**
     * Verify that the card can perform an authorization with a card.
     *
     * @test
     */
    public function cardCanPerformAuthorizationAndCreatesPayment(): void
    {
        $card = $this->createCardObject();
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);

        $authorization = $card->authorize(1.0, 'EUR', self::RETURN_URL);

        // verify authorization has been created
        $this->assertNotNull($authorization->getId());

        // verify payment object has been created
        $payment = $authorization->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        // verify resources are linked properly
        $this->assertSame($authorization, $payment->getAuthorization());
        $this->assertSame($card, $payment->getPaymentType());

        // verify the payment object has been updated properly
        $this->assertAmounts($payment, 1.0, 0.0, 1.0, 0.0);
        $this->assertTrue($payment->isPending());
    }

    /**
     * Verify the card can perform charges and creates a payment object doing so.
     *
     * @test
     */
    public function cardCanPerformChargeAndCreatesPaymentObject(): void
    {
        $card = $this->createCardObject();
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);

        // card recurring is disabled by default
        $this->assertFalse($card->isRecurring());

        $charge = $card->charge(1.0, 'EUR', self::RETURN_URL, null, null, null, null, false);

        // card recurring is activated through charge transaction
        /** @var Card $fetchedCard */
        $fetchedCard = $this->unzer->fetchPaymentType($card->getId());
        $this->assertTrue($fetchedCard->isRecurring());

        // verify charge has been created
        $this->assertNotNull($charge->getId());

        // verify payment object has been created
        $payment = $charge->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        // verify resources are linked properly
        $this->assertEquals($charge->expose(), $payment->getCharge($charge->getId())->expose());
        $this->assertSame($card, $payment->getPaymentType());

        // verify the payment object has been updated properly
        $this->assertAmounts($payment, 0.0, 1.0, 1.0, 0.0);
        $this->assertTrue($payment->isCompleted());
    }

    /**
     * Verify that a card object can be fetched from the api using its id.
     *
     * @test
     */
    public function cardCanBeFetched(): void
    {
        $card = $this->createCardObject();
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);
        $this->assertNotNull($card->getId());
        $this->assertNotNull($card->getCardHolder());

        /** @var Card $fetchedCard */
        $fetchedCard = $this->unzer->fetchPaymentType($card->getId());
        $this->assertNotNull($fetchedCard->getId());
        $this->assertEquals(ValueService::maskValue($card->getNumber()), $fetchedCard->getNumber());
        $this->assertEquals($card->getExpiryDate(), $fetchedCard->getExpiryDate());
        $this->assertEquals('***', $fetchedCard->getCvc());
        $this->assertEquals($card->getCardHolder(), $fetchedCard->getCardHolder());
    }

    /**
     * Verify the card can charge the full amount of the authorization and the payment state is updated accordingly.
     *
     * @test
     */
    public function fullChargeAfterAuthorize(): void
    {
        $card = $this->createCardObject();
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);

        $authorization = $card->authorize(1.0, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment = $authorization->getPayment();

        // pre-check to verify changes due to fullCharge call
        $this->assertAmounts($payment, 1.0, 0.0, 1.0, 0.0);
        $this->assertTrue($payment->isPending());

        $charge     = $this->unzer->chargeAuthorization($payment->getId());
        $paymentNew = $charge->getPayment();

        // verify payment has been updated properly
        $this->assertAmounts($paymentNew, 0.0, 1.0, 1.0, 0.0);
        $this->assertTrue($paymentNew->isCompleted());
    }

    /**
     * Verify the card can charge part of the authorized amount and the payment state is updated accordingly.
     *
     * @test
     */
    public function partialChargeAfterAuthorization(): void
    {
        $card          = $this->createCardObject();
        /** @var Card $card */
        $card          = $this->unzer->createPaymentType($card);
        $authorization = $this->unzer->authorize(
            100.0,
            'EUR',
            $card,
            self::RETURN_URL,
            null,
            null,
            null,
            null,
            false
        );

        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        $charge   = $this->unzer->chargeAuthorization($payment->getId(), 20);
        $payment1 = $charge->getPayment();
        $this->assertAmounts($payment1, 80.0, 20.0, 100.0, 0.0);
        $this->assertTrue($payment1->isPartlyPaid());

        $charge   = $this->unzer->chargeAuthorization($payment->getId(), 20);
        $payment2 = $charge->getPayment();
        $this->assertAmounts($payment2, 60.0, 40.0, 100.0, 0.0);
        $this->assertTrue($payment2->isPartlyPaid());

        $charge   = $this->unzer->chargeAuthorization($payment->getId(), 60);
        $payment3 = $charge->getPayment();
        $this->assertAmounts($payment3, 00.0, 100.0, 100.0, 0.0);
        $this->assertTrue($payment3->isCompleted());
    }

    /**
     * Verify that an exception is thrown when trying to charge more than authorized.
     *
     * @test
     */
    public function exceptionShouldBeThrownWhenChargingMoreThenAuthorized(): void
    {
        $card          = $this->createCardObject();
        /** @var Card $card */
        $card          = $this->unzer->createPaymentType($card);
        $authorization = $card->authorize(100.0000, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment       = $authorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        $charge   = $this->unzer->chargeAuthorization($payment->getId(), 50);
        $payment1 = $charge->getPayment();
        $this->assertAmounts($payment1, 50.0, 50.0, 100.0, 0.0);
        $this->assertTrue($payment1->isPartlyPaid());

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_CHARGED_AMOUNT_HIGHER_THAN_EXPECTED);
        $this->unzer->chargeAuthorization($payment->getId(), 70);
    }

    /**
     * Verify the card payment can be charged until it is fully charged and the payment is updated accordingly.
     *
     * @test
     */
    public function partialAndFullChargeAfterAuthorization(): void
    {
        $card          = $this->createCardObject();
        /** @var Card $card */
        $card          = $this->unzer->createPaymentType($card);
        $authorization = $card->authorize(100.0000, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment       = $authorization->getPayment();

        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        $charge   = $this->unzer->chargeAuthorization($payment->getId(), 20);
        $payment1 = $charge->getPayment();
        $this->assertAmounts($payment1, 80.0, 20.0, 100.0, 0.0);
        $this->assertTrue($payment1->isPartlyPaid());

        $charge   = $this->unzer->chargeAuthorization($payment->getId());
        $payment2 = $charge->getPayment();
        $this->assertAmounts($payment2, 0.0, 100.0, 100.0, 0.0);
        $this->assertTrue($payment2->isCompleted());
    }

    /**
     * Authorization can be fetched.
     *
     * @test
     */
    public function authorizationShouldBeFetchable(): void
    {
        $card          = $this->createCardObject();
        /** @var Card $card */
        $card          = $this->unzer->createPaymentType($card);
        $authorization = $card->authorize(100.0000, 'EUR', self::RETURN_URL);
        $payment       = $authorization->getPayment();

        $fetchedAuthorization = $this->unzer->fetchAuthorization($payment->getId());
        $this->assertEquals($fetchedAuthorization->getId(), $authorization->getId());
    }

    /**
     * @test
     */
    public function fullCancelAfterCharge(): void
    {
        $card    = $this->createCardObject();
        /** @var Card $card */
        $card    = $this->unzer->createPaymentType($card);
        $charge  = $card->charge(100.0, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment = $charge->getPayment();

        $this->assertAmounts($payment, 0.0, 100.0, 100.0, 0.0);
        $this->assertTrue($payment->isCompleted());

        $payment->cancelAmount();
        $this->assertAmounts($payment, 0.0, 0.0, 100.0, 100.0);
        $this->assertTrue($payment->isCanceled());
    }

    /**
     * Verify a card payment can be cancelled after being fully charged.
     *
     * @test
     */
    public function fullCancelOnFullyChargedPayment(): void
    {
        $card = $this->createCardObject();
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);

        $authorization = $card->authorize(100.0000, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment       = $authorization->getPayment();

        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        $payment->charge(10.0);
        $this->assertAmounts($payment, 90.0, 10.0, 100.0, 0.0);
        $this->assertTrue($payment->isPartlyPaid());

        $payment->charge(90.0);
        $this->assertAmounts($payment, 0.0, 100.0, 100.0, 0.0);
        $this->assertTrue($payment->isCompleted());

        $cancellation = $payment->cancelAmount();
        $this->assertNotEmpty($cancellation);
        $this->assertAmounts($payment, 0.0, 0.0, 100.0, 100.0);
        $this->assertTrue($payment->isCanceled());
    }

    /**
     * Full cancel on partly charged auth canceled charges.
     *
     * @test
     */
    public function fullCancelOnPartlyPaidAuthWithCanceledCharges(): void
    {
        $card = $this->createCardObject();
        /** @var Card $card */
        $card = $this->unzer->createPaymentType($card);

        $authorization = $card->authorize(100.0000, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment       = $authorization->getPayment();

        $payment->charge(10.0);
        $this->assertAmounts($payment, 90.0, 10.0, 100.0, 0.0);

        $charge = $payment->charge(10.0);
        $this->assertAmounts($payment, 80.0, 20.0, 100.0, 0.0);
        $this->assertTrue($payment->isPartlyPaid());

        $charge->cancel();
        $this->assertAmounts($payment, 80.0, 10.0, 100.0, 10.0);
        $this->assertTrue($payment->isPartlyPaid());

        $payment->cancelAmount();
        $this->assertTrue($payment->isCanceled());
    }

    /**
     * Verify card charge can be canceled.
     *
     * @test
     */
    public function cardChargeCanBeCanceled(): void
    {
        /** @var Card $card */
        $card   = $this->unzer->createPaymentType($this->createCardObject());
        $charge = $card->charge(100.0, 'EUR', self::RETURN_URL, null, null, null, null, false);

        $cancel = $charge->cancel();
        $this->assertNotNull($cancel);
        $this->assertNotEmpty($cancel->getId());
    }

    /**
     * Verify card authorize can be canceled.
     *
     * @test
     */
    public function cardAuthorizeCanBeCanceled(): void
    {
        /** @var Card $card */
        $card      = $this->unzer->createPaymentType($this->createCardObject());
        $authorize = $card->authorize(100.0, 'EUR', self::RETURN_URL, null, null, null, null, false);

        $cancel = $authorize->cancel();
        $this->assertNotNull($cancel);
        $this->assertNotEmpty($cancel->getId());
    }

    //</editor-fold>

    //<editor-fold desc="Data Provider">

    /**
     * @return array
     */
    public function cardShouldBeCreatableDP(): array
    {
        $cardDetailsA = new CardDetails();
        $cardDetailsAObj          = (object)[
            'cardType'          => 'STANDARD',
            'account'           => 'DEBIT',
            'countryIsoA2'      => 'BE',
            'countryName'       => 'BELGIUM',
            'issuerName'        => 'MASTERCARD EUROPE',
            'issuerUrl'         => '',
            'issuerPhoneNumber' => ''
        ];
        $cardDetailsA->handleResponse($cardDetailsAObj);

        $cardDetailsB = new CardDetails();
        $cardDetailsBObj          = (object)[
            'cardType'          => '',
            'account'           => 'CREDIT',
            'countryIsoA2'      => 'US',
            'countryName'       => 'UNITED STATES',
            'issuerName'        => 'JPMORGAN CHASE BANK, N.A.',
            'issuerUrl'         => 'HTTP://WWW.JPMORGANCHASE.COM',
            'issuerPhoneNumber' => '1-212-270-6000'
        ];
        $cardDetailsB->handleResponse($cardDetailsBObj);

        return [
            'card type set'   => ['6799851000000032', $cardDetailsA],
            'issuer data set' => ['5453010000059543', $cardDetailsB]
        ];
    }

    //</editor-fold>
    public function cardShouldUseEmailDataProvider()
    {
        return[
            'email is set' => ['test@test.com', 'test@test.com'],
            'email is empty string' => ['', null],
            'email is empty/null' => [null, null],
        ];
    }

    public function supportedRecurrenceTypesDP(): array
    {
        return [
            'null' => [null, false],
            'empty string' => ['', false],
            'oneclick' => ['oneclick', false],
            'scheduled' => ['scheduled', true],
            'unscheduled' => ['unscheduled', true]
        ];
    }

    public function invalidRecurrenceTypesDP(): array
    {
        return [
            'invalid string' => ['invalid recurrence Type'],
            'number' => [42],
        ];
    }

    public function cardTransactionReturnsLiabilityIndicatorDP()
    {
        return [
            '6799851000000032' => ['6799851000000032'],
            '5453010000059543' => ['5453010000059543'],
            '4711100000000000' => ['4711100000000000'],
            '4012001037461114' => ['4012001037461114']
        ];
    }
}
