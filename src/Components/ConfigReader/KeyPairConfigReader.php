<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ConfigReader;

use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerPayment6\Installer\PaymentInstaller;

class KeyPairConfigReader
{
    private ConfigReaderInterface $configReader;

    public function __construct(ConfigReaderInterface $configReader)
    {
        $this->configReader = $configReader;
    }

    public function getPublicKey(KeyPairContext $keyPairContext): string
    {
        return $this->getKey($keyPairContext, 'publicKey', ConfigReader::CONFIG_KEY_PUBLIC_KEY);
    }

    public function getPrivateKey(KeyPairContext $keyPairContext): string
    {
        return $this->getKey($keyPairContext, 'privateKey', ConfigReader::CONFIG_KEY_PRIVATE_KEY);
    }

    public function getMatchingKey(string $key, string $salesChannelId): ?string
    {
        $config = $this->configReader->read($salesChannelId);

        if ($key === $config->get(ConfigReader::CONFIG_KEY_PUBLIC_KEY)) {
            return $config->get(ConfigReader::CONFIG_KEY_PRIVATE_KEY);
        }

        if ($key === $config->get(ConfigReader::CONFIG_KEY_PRIVATE_KEY)) {
            return $config->get(ConfigReader::CONFIG_KEY_PUBLIC_KEY);
        }

        foreach ($config->get(ConfigReader::CONFIG_KEY_PAYLATER_INVOICE) as $keyPairConfig) {
            if ($keyPairConfig['publicKey'] === $key) {
                return $keyPairConfig['privateKey'];
            }

            if ($keyPairConfig['privateKey'] === $key) {
                return $keyPairConfig['publicKey'];
            }
        }

        foreach ($config->get(ConfigReader::CONFIG_KEY_PAYLATER_INSTALLMENT) as $keyPairConfig) {
            if ($keyPairConfig['publicKey'] === $key) {
                return $keyPairConfig['privateKey'];
            }

            if ($keyPairConfig['privateKey'] === $key) {
                return $keyPairConfig['publicKey'];
            }
        }

        return null;
    }

    private function getKey(KeyPairContext $keyPairContext, string $keyPairConfigKey, string $defaultConfigKey): string
    {
        $configData = $this->configReader->read($keyPairContext->getSalesChannelId());

        $privateKey = $configData->get($defaultConfigKey);

        if ($keyPairContext->getPaymentMethodId() === PaymentInstaller::PAYMENT_ID_PAYLATER_INSTALLMENT) {
            $configKey = ConfigReader::CONFIG_KEY_PAYLATER_INSTALLMENT;
        } elseif ($keyPairContext->getPaymentMethodId() === PaymentInstaller::PAYMENT_ID_PAYLATER_INVOICE) {
            $configKey = ConfigReader::CONFIG_KEY_PAYLATER_INVOICE;
        }

        if (!isset($configKey)) {
            return $privateKey;
        }

        $keyPairConfigs = $configData->get($configKey);

        foreach ($keyPairConfigs as $keyPairConfig) {
            $customerType = $keyPairContext->isB2B() ? 'b2b' : 'b2c';
            $currentKey   = sprintf('%s-%s', $customerType, strtolower($keyPairContext->getCurrencyIsoCode()));

            if ($keyPairConfig['key'] === $currentKey) {
                return $keyPairConfig[$keyPairConfigKey];
            }
        }

        return $privateKey;
    }
}
