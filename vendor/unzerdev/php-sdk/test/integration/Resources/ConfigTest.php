<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify Config functionalities.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\Resources;

use UnzerSDK\Constants\CustomerTypes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Config;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Resources\PaymentTypes\PaylaterInvoice;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebitSecured;
use UnzerSDK\Resources\PaymentTypes\Sofort;
use UnzerSDK\test\BaseIntegrationTest;

class ConfigTest extends BaseIntegrationTest
{
    /**
     * Invoice secured config is fetchable.
     *
     * @test
     */
    public function verifyPaylaterinvoiceConfigIsFetchable()
    {
        $config = (new Config())
            ->setCustomerType(CustomerTypes::B2C)
            ->setCountry('DE');

        $this->assertNull($config->getDataPrivacyConsent());
        $this->assertNull($config->getDataPrivacyDeclaration());
        $this->assertNull($config->getTermsAndConditions());

        $this->getUnzerObject()->fetchConfig(new PaylaterInvoice(), $config);

        $this->assertNotNull($config->getDataPrivacyConsent());
        $this->assertNotNull($config->getDataPrivacyDeclaration());
        $this->assertNotNull($config->getTermsAndConditions());
    }

    /**
     * Payment types that do not support config throw exception .
     *
     * @dataProvider verifyPaymentTypesWithNoConfigThrowExeptionDp
     *
     * @test
     *
     * @param mixed $paymentType
     */
    public function verifyPaymentTypesWithNoConfigThrowExeption($paymentType)
    {
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionMessage('The given payment method config is not found.');
        $this->getUnzerObject()->fetchConfig($paymentType);
    }

    /** Provide Payment types that have no config to test error case.
     * @return array
     */
    public function verifyPaymentTypesWithNoConfigThrowExeptionDp(): array
    {
        return [
            'sofort' => [new Sofort()],
            'paypal' => [new Paypal()],
            'card' => [new Card(null, null, null)],
            'directDebit' => [new SepaDirectDebit(null)],
            'directDebitSecured' => [new SepaDirectDebitSecured(null)],
            'instalment' => [new InstallmentSecured(null)],
        ];
    }
}
