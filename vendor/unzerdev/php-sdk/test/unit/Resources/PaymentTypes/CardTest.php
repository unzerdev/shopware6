<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of Card payment type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use UnzerSDK\Resources\EmbeddedResources\CardDetails;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\test\BasePaymentTest;
use RuntimeException;
use stdClass;

class CardTest extends BasePaymentTest
{
    private const TEST_ID = 's-crd-l4bbx7ory1ec';
    private const TEST_NUMBER = '444433******1111';
    private const TEST_BRAND = 'VISA';
    private const TEST_CVC = '***';
    private const TEST_EXPIRY_DATE = '03/2020';
    private const TEST_EMAIL = 'test@test.com';
    private const TEST_HOLDER = 'Max Mustermann';

    private $number     = '4111111111111111';
    private $expiryDate = '12/2030';

    /** @var Card $card */
    private $card;

    //<editor-fold desc="Data Providers">

    /**
     * @return array
     */
    public function expiryDateDataProvider(): array
    {
        return [
            ['11/22', '11/2022'],
            ['1/12', '01/2012']
        ];
    }

    /**
     * @return array
     */
    public function invalidExpiryDateDataProvider(): array
    {
        return [
            ['12'],
            ['/12'],
            ['1/1.2'],
            ['asd/12'],
            ['1/asdf'],
            ['13/12'],
            ['12/20199'],
            ['0/12']
        ];
    }

    //</editor-fold>

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->card = new Card($this->number, $this->expiryDate);
    }

    /**
     * Verify the resource data is set properly.
     *
     * @test
     */
    public function constructorShouldSetParameters(): void
    {
        $number     = '4111111111111111';
        $expiryDate = '12/2030';
        $card       = new Card($number, $expiryDate);

        $this->assertEquals($number, $card->getNumber());
        $this->assertEquals($expiryDate, $card->getExpiryDate());

        $geoLocation = $card->getGeoLocation();
        $this->assertNull($geoLocation->getClientIp());
        $this->assertNull($geoLocation->getCountryCode());
    }

    /**
     * Verify that email address can be set in card constructor.
     *
     * @test
     */
    public function constructorShouldSetEmail()
    {
        // when
        $number     = '4111111111111111';
        $expiryDate = '12/2030';
        $email      = 'test@email.com';
        $card       = new Card($number, $expiryDate, $email);

        // then
        $this->assertEquals($number, $card->getNumber());
        $this->assertEquals($expiryDate, $card->getExpiryDate());
        $this->assertEquals($email, $card->getEmail());

        $geoLocation = $card->getGeoLocation();
        $this->assertNull($geoLocation->getClientIp());
        $this->assertNull($geoLocation->getCountryCode());
    }

    /**
     * Verify expiryDate year is extended if it is the short version.
     *
     * @test
     *
     * @dataProvider expiryDateDataProvider
     *
     * @param string $testData
     * @param string $expected
     */
    public function expiryDateShouldBeExtendedToLongVersion($testData, $expected): void
    {
        $this->card->setExpiryDate($testData);
        $this->assertEquals($expected, $this->card->getExpiryDate());
    }

    /**
     * Verify invalid expiryDate throws Exception.
     *
     * @test
     *
     * @dataProvider invalidExpiryDateDataProvider
     *
     * @param string $testData
     */
    public function yearOfExpiryDateShouldBeExtendedToLongVersion($testData): void
    {
        $this->expectException(RuntimeException::class);
        $this->card->setExpiryDate($testData);
    }

    /**
     * Verify setting ExpiryDate null does nothing.
     * This needs to be allowed in order to be able to instantiate the Card without any data to fetch
     * it afterwards by just setting the id.
     *
     * @test
     */
    public function verifySettingExpiryDateNullChangesNothing(): void
    {
        $card = new Card(null, null);
        $this->assertEquals(null, $card->getExpiryDate());

        $this->assertEquals('12/2030', $this->card->getExpiryDate());
        $this->card->setExpiryDate(null);
        $this->assertEquals('12/2030', $this->card->getExpiryDate());
    }

    /**
     * Verify setting cvc.
     *
     * @test
     */
    public function verifyCvcCanBeSetAndChanged(): void
    {
        $this->assertEquals(null, $this->card->getCvc());
        $this->card->setCvc('123');
        $this->assertEquals('123', $this->card->getCvc());
        $this->card->setCvc('456');
        $this->assertEquals('456', $this->card->getCvc());
    }

    /**
     * Verify setting holder.
     *
     * @test
     */
    public function verifyHolderCanBeSetAndChanged(): void
    {
        $this->assertEquals(null, $this->card->getCardHolder());
        $this->card->setCardHolder('Julia Heideich');
        $this->assertEquals('Julia Heideich', $this->card->getCardHolder());
        $this->card->setCardHolder(self::TEST_HOLDER);
        $this->assertEquals(self::TEST_HOLDER, $this->card->getCardHolder());
    }

    /**
     * verify that email address can be set as Expected.
     *
     * @test
     */
    public function verifyEmailCanBeSetAndChanged()
    {
        $this->assertEquals(null, $this->card->getEmail());
        $this->card->setEmail('test@test.de');
        $this->assertEquals('test@test.de', $this->card->getEmail());
        $this->card->setEmail('test2@test.de');
        $this->assertEquals('test2@test.de', $this->card->getEmail());
    }

    /**
     * Verify card3ds flag.
     *
     * @test
     */
    public function card3dsFlagShouldBeSettableInCardResource(): void
    {
        $this->assertNull($this->card->get3ds());
        $this->assertArrayNotHasKey('3ds', $this->card->expose());
        $this->assertArrayNotHasKey('card3ds', $this->card->expose());

        $this->card->set3ds(true);
        $this->assertTrue($this->card->get3ds());
        $this->assertTrue($this->card->expose()['3ds']);
        $this->assertArrayNotHasKey('card3ds', $this->card->expose());

        $this->card->set3ds(false);
        $this->assertFalse($this->card->get3ds());
        $this->assertFalse($this->card->expose()['3ds']);
        $this->assertArrayNotHasKey('card3ds', $this->card->expose());
    }

    /**
     * Verify setting brand.
     *
     * @test
     */
    public function verifyCardCanBeUpdated(): void
    {
        $newGeoLocation = (object)['clientIp' => 'client ip', 'countryCode' => 'country code'];
        $newValues = (object)[
            'id' => self::TEST_ID,
            'number' => self::TEST_NUMBER,
            'brand' => self::TEST_BRAND,
            'cvc' => self::TEST_CVC,
            'email' => self::TEST_EMAIL,
            'expiryDate' => self::TEST_EXPIRY_DATE,
            'cardHolder' => self::TEST_HOLDER,
            'geolocation' => $newGeoLocation
        ];

        $this->card->handleResponse($newValues);

        $this->assertEquals(self::TEST_ID, $this->card->getId());
        $this->assertEquals(self::TEST_NUMBER, $this->card->getNumber());
        $this->assertEquals(self::TEST_BRAND, $this->card->getBrand());
        $this->assertEquals(self::TEST_CVC, $this->card->getCvc());
        $this->assertEquals(self::TEST_EMAIL, $this->card->getEmail());
        $this->assertEquals(self::TEST_EXPIRY_DATE, $this->card->getExpiryDate());
        $this->assertEquals(self::TEST_HOLDER, $this->card->getCardHolder());
        $cardDetails = $this->card->getCardDetails();
        $this->assertNull($cardDetails);

        $geoLocation = $this->card->getGeoLocation();
        $this->assertEquals('client ip', $geoLocation->getClientIp());
        $this->assertEquals('country code', $geoLocation->getCountryCode());

        $cardDetails = new stdClass();
        $cardDetails->cardType = 'my card type';
        $cardDetails->account = 'CREDIT';
        $cardDetails->countryIsoA2 = 'DE';
        $cardDetails->countryName = 'Germany';
        $cardDetails->issuerName = 'my issuer name';
        $cardDetails->issuerUrl = 'https://my.issuer.url';
        $cardDetails->issuerPhoneNumber = '+49 6221 6471-400';
        $newValues->cardDetails = $cardDetails;

        $this->card->handleResponse($newValues);
        $this->assertEquals(self::TEST_ID, $this->card->getId());
        $this->assertEquals(self::TEST_NUMBER, $this->card->getNumber());
        $this->assertEquals(self::TEST_BRAND, $this->card->getBrand());
        $this->assertEquals(self::TEST_CVC, $this->card->getCvc());
        $this->assertEquals(self::TEST_EXPIRY_DATE, $this->card->getExpiryDate());
        $this->assertEquals(self::TEST_HOLDER, $this->card->getCardHolder());
        $details = $this->card->getCardDetails();
        $this->assertInstanceOf(CardDetails::class, $details);
        $this->assertEquals('my card type', $details->getCardType());
        $this->assertEquals('CREDIT', $details->getAccount());
        $this->assertEquals('DE', $details->getCountryIsoA2());
        $this->assertEquals('Germany', $details->getCountryName());
        $this->assertEquals('my issuer name', $details->getIssuerName());
        $this->assertEquals('https://my.issuer.url', $details->getIssuerUrl());
        $this->assertEquals('+49 6221 6471-400', $details->getIssuerPhoneNumber());
    }
}
