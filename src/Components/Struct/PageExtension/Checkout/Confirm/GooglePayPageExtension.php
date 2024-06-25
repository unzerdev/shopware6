<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;

class GooglePayPageExtension extends Struct
{
    public const EXTENSION_NAME = 'unzerGooglePay';

    protected array $publicConfig = [];

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
