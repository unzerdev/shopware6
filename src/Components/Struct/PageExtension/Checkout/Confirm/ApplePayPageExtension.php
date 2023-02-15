<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;

class ApplePayPageExtension extends Struct
{
    public const EXTENSION_NAME = 'unzerApplePay';

    /** @var string[] */
    protected $supportedNetworks = ['masterCard', 'visa'];

    public function getSupportedNetworks(): array
    {
        return $this->supportedNetworks;
    }
}
