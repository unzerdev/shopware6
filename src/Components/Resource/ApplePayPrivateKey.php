<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Resource;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\AbstractUnzerResource;

class ApplePayPrivateKey extends AbstractUnzerResource
{
    /** @var string */
    protected $format = 'PEM';
    /** @var string */
    protected $type = 'private-key';
    /** @var string */
    protected $certificate;

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCertificate(): string
    {
        return $this->certificate;
    }

    public function setCertificate(string $certificate): self
    {
        $this->certificate = $certificate;

        return $this;
    }

    protected function getResourcePath($httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'keypair/applepay/privatekeys';
    }
}
