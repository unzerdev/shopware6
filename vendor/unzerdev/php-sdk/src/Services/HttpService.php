<?php

namespace UnzerSDK\Services;

use UnzerSDK\Adapter\CurlAdapter;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Unzer;
use UnzerSDK\Resources\AbstractUnzerResource;
use RuntimeException;

use function in_array;

use const PHP_VERSION;

/**
 * This service provides for functionalities concerning http transactions.
 *
 * @link  https://docs.unzer.com/
 *
 */
class HttpService
{
    private const URL_PART_STAGING_ENVIRONMENT = 'stg';
    private const URL_PART_DEVELOPMENT_ENVIRONMENT = 'dev';
    private const URL_PART_SANDBOX_ENVIRONMENT = 'sbx';

    /** @var HttpAdapterInterface $httpAdapter */
    private $httpAdapter;

    /** @var EnvironmentService $environmentService */
    private $environmentService;

    /**
     * Returns the currently set HttpAdapter.
     * If it is not set it will create a CurlRequest by default and return it.
     *
     * @return HttpAdapterInterface
     *
     * @throws RuntimeException
     */
    public function getAdapter(): HttpAdapterInterface
    {
        if (!$this->httpAdapter instanceof HttpAdapterInterface) {
            $this->httpAdapter = new CurlAdapter();
        }
        return $this->httpAdapter;
    }

    /**
     * @param HttpAdapterInterface $httpAdapter
     *
     * @return HttpService
     */
    public function setHttpAdapter(HttpAdapterInterface $httpAdapter): HttpService
    {
        $this->httpAdapter = $httpAdapter;
        return $this;
    }

    /**
     * @return EnvironmentService
     */
    public function getEnvironmentService(): EnvironmentService
    {
        if (!$this->environmentService instanceof EnvironmentService) {
            $this->environmentService = new EnvironmentService();
        }
        return $this->environmentService;
    }

    /**
     * @param EnvironmentService $environmentService
     *
     * @return HttpService
     */
    public function setEnvironmentService(EnvironmentService $environmentService): HttpService
    {
        $this->environmentService = $environmentService;
        return $this;
    }

    /**
     * send post request to payment server
     *
     * @param                        $uri        string|null uri of the target system
     * @param ?AbstractUnzerResource $resource
     * @param string                 $httpMethod
     * @param string                 $apiVersion
     *
     * @return string
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function send(
        ?string                $uri = null,
        ?AbstractUnzerResource $resource = null,
        string                 $httpMethod = HttpAdapterInterface::REQUEST_GET,
        string                 $apiVersion = Unzer::API_VERSION
    ): string {
        if (!$resource instanceof AbstractUnzerResource) {
            throw new RuntimeException('Transfer object is empty!');
        }
        $unzerObj = $resource->getUnzerObject();

        // perform request
        $requestUrl = $this->buildRequestUrl($uri, $apiVersion, $unzerObj);
        $payload = $resource->jsonSerialize();
        $headers = $this->composeHttpHeaders($unzerObj);
        $this->initRequest($requestUrl, $payload, $httpMethod, $headers);
        $httpAdapter  = $this->getAdapter();
        $response     = $httpAdapter->execute();
        $responseCode = $httpAdapter->getResponseCode();
        $httpAdapter->close();

        // handle response
        try {
            $this->handleErrors($responseCode, $response);
        } finally {
            $this->debugLog($unzerObj, $payload, $headers, $responseCode, $httpMethod, $requestUrl, $response);
        }

        return $response;
    }

    /**
     * Initializes and returns the http request object.
     *
     * @param string $uri
     * @param string $payload
     * @param string $httpMethod
     * @param array  $httpHeaders
     *
     * @throws RuntimeException
     */
    private function initRequest(string $uri, string $payload, string $httpMethod, array $httpHeaders): void
    {
        $httpAdapter = $this->getAdapter();
        $httpAdapter->init($uri, $payload, $httpMethod);
        $httpAdapter->setUserAgent(Unzer::SDK_TYPE);
        $httpAdapter->setHeaders($httpHeaders);
    }

    /**
     * Handles error responses by throwing an UnzerApiException with the returned messages and error code.
     * Returns doing nothing if no error occurred.
     *
     * @param string      $responseCode
     * @param string|null $response
     *
     * @throws UnzerApiException
     */
    private function handleErrors(string $responseCode, ?string $response): void
    {
        if ($response === null) {
            throw new UnzerApiException('The Request returned a null response!');
        }

        $responseObject = json_decode($response, false);
        if (!is_numeric($responseCode) || (int)$responseCode >= 400 || isset($responseObject->errors)) {
            $code            = null;
            $errorId         = null;
            $customerMessage = $code;
            $merchantMessage = $customerMessage;
            if (isset($responseObject->errors[0])) {
                $errors          = $responseObject->errors[0];
                $merchantMessage = $errors->merchantMessage ?? '';
                $customerMessage = $errors->customerMessage ?? '';
                $code            = $errors->code ?? '';
            }
            if (isset($responseObject->id)) {
                $errorId = $responseObject->id;
                if (IdService::getResourceTypeFromIdString($errorId) !== 'err') {
                    $errorId = null;
                }
            }

            throw new UnzerApiException($merchantMessage, $customerMessage, $code, $errorId);
        }
    }

    /**
     * @param Unzer       $unzerObj
     * @param string      $payload
     * @param mixed       $headers
     * @param string      $responseCode
     * @param string      $httpMethod
     * @param string      $url
     * @param string|null $response
     */
    public function debugLog(
        Unzer  $unzerObj,
        string $payload,
        $headers,
        string    $responseCode,
        string $httpMethod,
        string $url,
        ?string $response
    ): void {
        // mask auth string
        $authHeader = explode(' ', $headers['Authorization']);
        $authHeader[1] = ValueService::maskValue($authHeader[1]);
        $headers['Authorization'] = implode(' ', $authHeader);

        // log request
        $unzerObj->debugLog($httpMethod . ': ' . $url);
        $writingOperations = [
            HttpAdapterInterface::REQUEST_POST,
            HttpAdapterInterface::REQUEST_PUT,
            HttpAdapterInterface::REQUEST_PATCH
        ];
        $unzerObj->debugLog('Headers: ' . json_encode($headers, JSON_UNESCAPED_SLASHES));
        if (in_array($httpMethod, $writingOperations, true)) {
            $unzerObj->debugLog('Request: ' . $payload);
        }

        // log response
        $response = $response ?? '';
        $unzerObj->debugLog(
            'Response: (' . $responseCode . ') ' .
            json_encode(json_decode($response, false), JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Returns the environment url.
     *
     * @param string $uri
     * @param string $apiVersion
     * @param Unzer  $unzer
     *
     * @return string
     */
    private function buildRequestUrl(string $uri, string $apiVersion, Unzer $unzer): string
    {
        $envPrefix = $this->getEnvironmentPrefix($unzer);
        return "https://" . $envPrefix . Unzer::BASE_URL . "/" . $apiVersion . $uri;
    }

    /**
     * @param Unzer $unzer
     *
     * @return array
     */
    public function composeHttpHeaders(Unzer $unzer): array
    {
        $locale      = $unzer->getLocale();
        $clientIp    = $unzer->getClientIp();
        $key         = $unzer->getKey();
        $httpHeaders = [
            'Authorization' => 'Basic ' . base64_encode($key . ':'),
            'Content-Type'  => 'application/json',
            'SDK-VERSION'   => Unzer::SDK_VERSION,
            'SDK-TYPE'      => Unzer::SDK_TYPE,
            'PHP-VERSION'   => PHP_VERSION
        ];
        if (!empty($locale)) {
            $httpHeaders['Accept-Language'] = $locale;
        }
        if (!empty($clientIp)) {
            $httpHeaders['CLIENTIP'] = $clientIp;
        }

        return $httpHeaders;
    }

    /** Determine the environment to be used for Api calls and returns the prefix for it.
     * Production environment has no prefix.
     *
     * @param Unzer $unzer
     *
     * @return string
     */
    private function getEnvironmentPrefix(Unzer $unzer): string
    {
        // Production Environment uses no prefix.
        if ($this->isProductionKey($unzer->getKey())) {
            return '';
        }

        switch ($this->getEnvironmentService()->getPapiEnvironment()) {
            case EnvironmentService::ENV_VAR_VALUE_STAGING_ENVIRONMENT:
                $envPrefix = self::URL_PART_STAGING_ENVIRONMENT;
                break;
            case EnvironmentService::ENV_VAR_VALUE_DEVELOPMENT_ENVIRONMENT:
                $envPrefix = self::URL_PART_DEVELOPMENT_ENVIRONMENT;
                break;
            default:
                $envPrefix = self::URL_PART_SANDBOX_ENVIRONMENT;
        }
        return $envPrefix . '-';
    }

    /** Determine whether key is for production environment.
     *
     * @param string $privateKey
     *
     * @return bool
     */
    private function isProductionKey(string $privateKey): bool
    {
        return strpos($privateKey, 'p') === 0;
    }
}
