<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ConfigReader;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use UnzerPayment6\Components\Struct\Configuration;

class ConfigReader implements ConfigReaderInterface
{
    /** @var string */
    public const SYSTEM_CONFIG_DOMAIN = 'UnzerPayment6.settings.';

    public const CONFIG_KEY_PUBLIC_KEY       = 'publicKey';
    public const CONFIG_KEY_PRIVATE_KEY      = 'privateKey';
    public const CONFIG_KEY_TEST_DATA        = 'testData';
    public const CONFIG_KEY_EXTENDED_LOGGING = 'extendedLogging';

    public const CONFIG_KEY_BOOKING_MODE_CARD                                = 'bookingModeCreditCard';
    public const CONFIG_KEY_BOOKING_MODE_PAYPAL                              = 'bookingModePayPal';
    public const CONFIG_KEY_BOOKING_MODE_APPLE_PAY                           = 'bookingModeApplePay';
    public const CONFIG_KEY_APPLE_PAY_PAYMENT_PROCESSING_CERTIFICATE_ID      = 'applePayPaymentProcessingCertificateId';
    public const CONFIG_KEY_APPLE_PAY_MERCHANT_IDENTIFICATION_CERTIFICATE_ID = 'applePayMerchantIdentificationCertificateId';
    public const CONFIG_KEY_APPLE_PAY_MERCHANT_IDENTIFIER                    = 'applePayMerchantIdentifier';
    public const CONFIG_KEY_PAYLATER_INSTALLMENT                             = 'paylaterInstallment';
    public const CONFIG_KEY_PAYLATER_INVOICE                                 = 'paylaterInvoice';
    public const CONFIG_KEY_PAYLATER_DIRECT_DEBIT_SECURED                    = 'paylaterDirectDebitSecured';

    public const CONFIG_KEY_SHIPPING_STATUS = 'statusForAutomaticShippingNotification';

    public const CONFIG_KEY_GOOGLE_PAY_BOOKING_MODE = 'googlePayBookingMode';
    public const CONFIG_KEY_GOOGLE_PAY_MERCHANT_ID = 'googlePayMerchantId';
    public const CONFIG_KEY_GOOGLE_PAY_MERCHANT_NAME = 'googlePayMerchantName';
    public const CONFIG_KEY_GOOGLE_PAY_CHANNEL_ID = 'googlePayChannelId';
    public const CONFIG_KEY_GOOGLE_PAY_COUNTRY_CODE = 'googlePayCountryCode';
    public const CONFIG_KEY_GOOGLE_PAY_CREDIT_CARDS_ALLOWED = 'googlePayCreditCardsAllowed';
    public const CONFIG_KEY_GOOGLE_PAY_PREPAID_CARDS_ALLOWED = 'googlePayPrepaidCardsAllowed';
    public const CONFIG_KEY_GOOGLE_PAY_CARD_NETWORKS = 'googlePayCardNetworks';
    public const CONFIG_KEY_GOOGLE_PAY_BUTTON_COLOR = 'googlePayButtonColor';
    public const CONFIG_KEY_GOOGLE_PAY_BUTTON_SIZE_MODE = 'googlePayButtonSizeMode';

    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function read(string $salesChannelId = '', bool $fallback = true): Configuration
    {
        $values = $this->systemConfigService->getDomain(
            self::SYSTEM_CONFIG_DOMAIN,
            $salesChannelId,
            $fallback
        );

        $config = [];

        foreach ($values as $key => $value) {
            $property = substr($key, strlen(self::SYSTEM_CONFIG_DOMAIN));

            if (!empty($property)) {
                $config[$property] = $value;
            }
        }

        return new Configuration($config);
    }
}
