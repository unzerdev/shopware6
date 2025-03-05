<?php

namespace UnzerSDK\Apis;

use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Unzer;

/**
 * This class holds the API request data.
 */
class ApiRequest
{
    private string $uri;
    private AbstractUnzerResource $resource;
    private string $httpMethod;
    private Unzer $unzerObject;
    private string $apiVersion;

    /**
     * @param string $uri
     * @param AbstractUnzerResource $resource
     * @param string $httpMethod
     * @param Unzer $unzerObject
     */
    public function __construct(string $uri, AbstractUnzerResource $resource, string $httpMethod, Unzer $unzerObject, string $apiVersion = Unzer::API_VERSION)
    {
        $this->uri = $uri;
        $this->apiVersion = $apiVersion;
        $this->httpMethod = $httpMethod;
        $this->resource = $resource;
        $this->unzerObject = $unzerObject;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getResource(): AbstractUnzerResource
    {
        return $this->resource;
    }

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function getUnzerObject(): Unzer
    {
        return $this->unzerObject;
    }

    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }
}
