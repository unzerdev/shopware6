<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Resource;

use stdClass;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\AbstractUnzerResource;

class ApplePayCertificate extends AbstractUnzerResource
{
    /** @var string */
    protected $format = 'PEM';
    /** @var string */
    protected $type = 'certificate';
    /** @var string */
    protected $privateKey;
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

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function setPrivateKey(string $privateKey): self
    {
        $this->privateKey = $privateKey;

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

    public function expose()
    {
        $data = parent::expose();

        if (!($data instanceof stdClass) && array_key_exists('privateKey', $data)) {
            $data['private-key'] = $data['privateKey'];
            unset($data['privateKey']);
        }

        return $data;
    }

    protected function getResourcePath($httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'keypair/applepay/certificates';
    }
}
