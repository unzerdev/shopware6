<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Resource;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\AbstractUnzerResource;

class ApplePayCertificate extends AbstractUnzerResource
{
    /** @var string */
    private $format = 'PEM';
    /** @var string */
    private $type = 'certificate';
    /** @var string */
    private $privateKey;
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

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function setPrivateKey(string $privateKey): void
    {
        $this->privateKey = $privateKey;
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
        return 'keypair/applepay/certificates';
    }
}
