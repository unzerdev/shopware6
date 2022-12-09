<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ApplePay;

class CertificateManager
{
    private const APPLE_PAY_CERTIFICATE_PATH                   = 'unzer_payment6_apple_pay_certificates';
    private const MERCHANT_IDENTIFICATION_CERTIFICATE_FILENAME = 'merchant-identification-certificate.pem';
    private const MERCHANT_IDENTIFICATION_KEY_FILENAME         = 'merchant-identification-privatekey.key';

    public function getMerchantIdentificationCertificatePath(string $salesChannelId): string
    {
        // TODO: Get path prefix via ConfigReader
        return sprintf('%s/%s/%s', self::APPLE_PAY_CERTIFICATE_PATH, $salesChannelId, self::MERCHANT_IDENTIFICATION_CERTIFICATE_FILENAME);
    }

    public function getMerchantIdentificationKeyPath(string $salesChannelId): string
    {
        // TODO: Get path prefix via ConfigReader
        return sprintf('%s/%s/%s', self::APPLE_PAY_CERTIFICATE_PATH, $salesChannelId, self::MERCHANT_IDENTIFICATION_KEY_FILENAME);
    }
}
