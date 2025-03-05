<?php

namespace UnzerSDK\Resources\Authentication;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Apis\TokenApiConfig;
use UnzerSDK\Resources\AbstractUnzerResource;

class Token extends AbstractUnzerResource
{
    public const URI = '/auth/token';

    /** @var string */
    protected $accessToken;

    public function getApiConfig(): string
    {
        return TokenApiConfig::class;
    }

    public function getUri(bool $appendId = true, string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return self::URI;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }
}
