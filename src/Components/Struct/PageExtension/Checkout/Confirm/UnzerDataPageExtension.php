<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;

class UnzerDataPageExtension extends Struct
{
    public const EXTENSION_NAME = 'unzerPaymentData';

    /** @var string */
    private $publicKey;

    /** @var string */
    private $locale;

    /** @var bool */
    private $showTestData;

    /** @var string */
    private $customerId;

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getShowTestData(): bool
    {
        return $this->showTestData;
    }

    public function setShowTestData(bool $showTestData): void
    {
        $this->showTestData = $showTestData;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }
}
