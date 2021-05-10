<?php


namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;


use Shopware\Core\Framework\Struct\Struct;

class UnzerDataPageExtension extends Struct
{
    public const EXTENSION_NAME = 'unzerPaymentData';

    /** @var string */
    private $publicKey;

    /** @var bool */
    private $showTestData;

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    public function getShowTestData(): bool
    {
        return $this->showTestData;
    }

    public function setShowTestData(bool $showTestData): void
    {
        $this->showTestData = $showTestData;
    }
}
