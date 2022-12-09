<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Resource;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\AbstractUnzerResource;

class ApplePayPrivateKey extends AbstractUnzerResource
{
    /** @var string */
    private $format = 'PEM';
    /** @var string */
    private $type = 'private-key';
    /** @var string */
    private $certificate;

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getCertificate(): string
    {
        return $this->certificate;
    }

    public function setCertificate(string $certificate): void
    {
        $this->certificate = $certificate;
    }

    protected function getResourcePath($httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'keypair/applepay/privatekeys';
    }
}
