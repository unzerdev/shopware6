<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Storefront;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\PaymentHandler\UnzerGooglePayPaymentHandler;
use UnzerPayment6\Components\Struct\Configuration;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\ApplePayV2PageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\GooglePayPageExtension;

class ExtensionFactory
{
    private ?Configuration $configData = null;

    public function __construct(
        private readonly ConfigReaderInterface $configReader,
        private readonly ClientFactoryInterface $clientFactory,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public function getGooglePayExtension(?string $salesChannelId = null): GooglePayPageExtension
    {
        $this->readConfig($salesChannelId);
        $extension = new GooglePayPageExtension();
        $extension->setPublicConfig([
            'merchantName' => $this->configData->get(ConfigReader::CONFIG_KEY_GOOGLE_PAY_MERCHANT_NAME),
            'merchantId' => $this->configData->get(ConfigReader::CONFIG_KEY_GOOGLE_PAY_MERCHANT_ID),
            'gatewayMerchantId' => $this->fetchGooglePayChannelId($salesChannelId),
            'countryCode' => $this->configData->get(ConfigReader::CONFIG_KEY_GOOGLE_PAY_COUNTRY_CODE),
            'allowedCardNetworks' => $this->configData->get(ConfigReader::CONFIG_KEY_GOOGLE_PAY_CARD_NETWORKS),
            'allowCreditCards' => $this->configData->get(ConfigReader::CONFIG_KEY_GOOGLE_PAY_CREDIT_CARDS_ALLOWED),
            'allowPrepaidCards' => $this->configData->get(ConfigReader::CONFIG_KEY_GOOGLE_PAY_PREPAID_CARDS_ALLOWED),
            'buttonColor' => $this->configData->get(ConfigReader::CONFIG_KEY_GOOGLE_PAY_BUTTON_COLOR),
            'buttonSizeMode' => $this->configData->get(ConfigReader::CONFIG_KEY_GOOGLE_PAY_BUTTON_SIZE_MODE),
        ]);

        return $extension;
    }

    public function getApplePayExtension(?string $salesChannelId = null): ApplePayV2PageExtension
    {
        $this->readConfig($salesChannelId);
        $extension = new ApplePayV2PageExtension();
        $extension->setPublicConfig([
            'countryCode' => 'DE', // TODO: Change based on what?
            'allowedCardNetworks' => $extension->getSupportedNetworks(),
            'merchantCapabilities' => $extension->getMerchantCapabilities(),
            'shopName' => $this->systemConfigService->get('core.basicInformation.shopName', $salesChannelId),
        ]);

        return $extension;
    }

    private function readConfig(?string $salesChannelId = null): void
    {
        $this->configData = $this->configReader->read($salesChannelId);
    }

    private function fetchGooglePayChannelId($salesChannelId = null)
    {
        $publicKey = $this->configData->get(ConfigReader::CONFIG_KEY_PUBLIC_KEY);
        $client = $this->clientFactory->createClientFromPublicKey($publicKey, (string) $salesChannelId);

        return UnzerGooglePayPaymentHandler::fetchChannelId($client);
    }
}
