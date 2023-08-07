<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ApplePay;

use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;

class CertificateManager
{
    private const APPLE_PAY_CERTIFICATE_PATH                   = 'unzer_payment6_apple_pay_certificates';
    private const MERCHANT_IDENTIFICATION_CERTIFICATE_FILENAME = 'merchant-identification-certificate.pem';
    private const MERCHANT_IDENTIFICATION_KEY_FILENAME         = 'merchant-identification-privatekey.key';

    private ConfigReaderInterface $configReader;

    public function __construct(ConfigReaderInterface $configReader)
    {
        $this->configReader = $configReader;
    }

    public function getMerchantIdentificationCertificatePath(?string $salesChannelId): string
    {
        $config = $this->configReader->read($salesChannelId);

        return sprintf('%s/%s/%s', self::APPLE_PAY_CERTIFICATE_PATH, $config->get(ConfigReader::CONFIG_KEY_APPLE_PAY_MERCHANT_IDENTIFICATION_CERTIFICATE_ID), self::MERCHANT_IDENTIFICATION_CERTIFICATE_FILENAME);
    }

    public function getMerchantIdentificationCertificatePathForUpdate(?string $salesChannelId): string
    {
        return sprintf('%s/%s/%s', self::APPLE_PAY_CERTIFICATE_PATH, $salesChannelId, self::MERCHANT_IDENTIFICATION_CERTIFICATE_FILENAME);
    }

    public function getMerchantIdentificationKeyPath(?string $salesChannelId): string
    {
        $config = $this->configReader->read($salesChannelId);

        return sprintf('%s/%s/%s', self::APPLE_PAY_CERTIFICATE_PATH, $config->get(ConfigReader::CONFIG_KEY_APPLE_PAY_MERCHANT_IDENTIFICATION_CERTIFICATE_ID), self::MERCHANT_IDENTIFICATION_KEY_FILENAME);
    }

    public function getMerchantIdentificationKeyPathForUpdate(?string $salesChannelId): string
    {
        return sprintf('%s/%s/%s', self::APPLE_PAY_CERTIFICATE_PATH, $salesChannelId, self::MERCHANT_IDENTIFICATION_KEY_FILENAME);
    }
}
