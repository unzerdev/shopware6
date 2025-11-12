<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;

class ApplePayV2PageExtension extends Struct
{
    public const EXTENSION_NAME = 'unzerApplePayV2';

    /**
     * @var string[]
     */
    protected array $supportedNetworks = ['masterCard', 'visa'];

    protected array $merchantCapabilities = ['supports3DS'];

    protected array $publicConfig = [];

    public function getSupportedNetworks(): array
    {
        return $this->supportedNetworks;
    }

    public function getMerchantCapabilities(): array
    {
        return $this->merchantCapabilities;
    }

    public function getPublicConfig(): array
    {
        return $this->publicConfig;
    }

    public function setPublicConfig(array $publicConfig): self
    {
        $this->publicConfig = $publicConfig;

        return $this;
    }
}
