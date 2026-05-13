<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;
use UnzerSDK\Constants\CompanyTypes;
use UnzerSDK\Resources\Customer;

class UnzerDataPageExtension extends Struct
{
    public const EXTENSION_NAME = 'unzerPaymentData';
    public const JS_LIBRARY_URL = 'https://static-v2.unzer.com/v2/ui-components/index.js';

    private string $publicKey;

    private string $locale;

    private bool $showTestData;

    private bool $blockButtonOnLoad = true;

    private ?array $keyPairConfig = null;

    private ?Customer $unzerCustomer;

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

    public function getBlockButtonOnLoad(): bool
    {
        return $this->blockButtonOnLoad;
    }

    public function setBlockButtonOnLoad(bool $blockButtonOnLoad): void
    {
        $this->blockButtonOnLoad = $blockButtonOnLoad;
    }

    public function getKeyPairConfig(): ?array
    {
        return $this->keyPairConfig;
    }

    public function setKeyPairConfig(?array $keyPairConfig): void
    {
        $this->keyPairConfig = $keyPairConfig;
    }

    public function getUnzerCustomer(): ?Customer
    {
        return $this->unzerCustomer;
    }

    public function setUnzerCustomer(?Customer $unzerCustomer): void
    {
        $this->unzerCustomer = $unzerCustomer;
    }

    public function getCompanyTypes(): array
    {
        return (new \ReflectionClass(CompanyTypes::class))->getConstants();
    }
}
