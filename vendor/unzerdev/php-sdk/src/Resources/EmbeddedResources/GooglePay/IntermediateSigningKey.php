<?php

namespace UnzerSDK\Resources\EmbeddedResources\GooglePay;

use stdClass;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\AbstractUnzerResource;

class IntermediateSigningKey extends AbstractUnzerResource
{
    /**
     * @var SignedKey $signedKey
     */
    protected $signedKey;

    /**
     * @var string[] $signedKey
     */
    protected $signatures;

    public function getSignedKey(): ?SignedKey
    {
        return $this->signedKey;
    }

    public function setSignedKey(SignedKey $signedKey): IntermediateSigningKey
    {
        $this->signedKey = $signedKey;
        return $this;
    }

    public function getSignatures(): array
    {
        return $this->signatures;
    }

    public function setSignatures(array $signatures): IntermediateSigningKey
    {
        $this->signatures = $signatures;
        return $this;
    }

    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        parent::handleResponse($response, $method);

        if (isset($response->signedKey)) {
            $this->signedKey = new SignedKey();
            $this->signedKey->handleResponse($response->signedKey);
        }
    }
}
