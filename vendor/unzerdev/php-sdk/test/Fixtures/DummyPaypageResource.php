<?php

namespace UnzerSDK\test\Fixtures;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Apis\PaypageAPIConfig;
use UnzerSDK\Resources\AbstractUnzerResource;

class DummyPaypageResource extends AbstractUnzerResource
{
    protected $response = '';

    public function getResponse(): string
    {
        return $this->response;
    }

    public function setResponse(string $response): DummyPaypageResource
    {
        $this->response = $response;
        return $this;
    }

    public function getApiConfig(): string
    {
        return PaypageAPIConfig::class;
    }

    public function getUri(bool $appendId = true, string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return '/dummy-paypage-uri';
    }
}
