<?php

/** @noinspection PhpComposerExtensionStubsInspection */
/**
 * This is a wrapper for the default http adapter (CURL).
 *
 * @link https://dev.unzer.com/
 *
 */

namespace UnzerSDK\Adapter;

use UnzerSDK\Unzer;
use UnzerSDK\Services\EnvironmentService;
use UnzerSDK\Exceptions\UnzerApiException;
use RuntimeException;

use function extension_loaded;
use function in_array;

class CurlAdapter implements HttpAdapterInterface
{
    private $request;

    /**
     * CurlAdapter constructor.
     *
     * @throws RuntimeException
     */
    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('Connection error php-curl not installed');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function init(string $url, string $payload = null, string $httpMethod = HttpAdapterInterface::REQUEST_GET): void
    {
        $timeout = EnvironmentService::getTimeout();
        $curlVerbose = EnvironmentService::isCurlVerbose();

        $this->request = curl_init($url);
        $this->setOption(CURLOPT_HEADER, 0);
        $this->setOption(CURLOPT_FAILONERROR, false);
        $this->setOption(CURLOPT_TIMEOUT, $timeout);
        $this->setOption(CURLOPT_CONNECTTIMEOUT, $timeout);
        $this->setOption(CURLOPT_HTTP200ALIASES, (array)400);
        $this->setOption(CURLOPT_CUSTOMREQUEST, $httpMethod);
        $this->setOption(CURLOPT_RETURNTRANSFER, 1);
        $this->setOption(CURLOPT_SSL_VERIFYPEER, 1);
        $this->setOption(CURLOPT_SSL_VERIFYHOST, 2);
        $this->setOption(CURLOPT_VERBOSE, $curlVerbose);
        $this->setOption(CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

        $postFieldMethods = [
            HttpAdapterInterface::REQUEST_POST,
            HttpAdapterInterface::REQUEST_PUT,
            HttpAdapterInterface::REQUEST_PATCH
        ];
        if (in_array($httpMethod, $postFieldMethods, true)) {
            $this->setOption(CURLOPT_POSTFIELDS, $payload);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function execute(): ?string
    {
        $response = curl_exec($this->request);
        $error    = curl_error($this->request);
        $errorNo  = curl_errno($this->request);

        switch ($errorNo) {
            case 0:
                return $response;
                break;
            case CURLE_OPERATION_TIMEDOUT:
                $errorMessage = 'Timeout: The Payment API seems to be not available at the moment!';
                break;
            default:
                $errorMessage = $error . ' (curl_errno: '. $errorNo . ').';
                break;
        }
        throw new UnzerApiException($errorMessage);
    }

    /**
     * {@inheritDoc}
     */
    public function getResponseCode(): string
    {
        return curl_getinfo($this->request, CURLINFO_HTTP_CODE);
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        curl_close($this->request);
    }

    /**
     * {@inheritDoc}
     */
    public function setHeaders(array $headers): void
    {
        array_walk($headers, static function (&$value, $key) {
            $value = $key . ': ' . $value;
        });

        $this->setOption(CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function setUserAgent($userAgent): void
    {
        $this->setOption(CURLOPT_USERAGENT, Unzer::SDK_TYPE);
    }

    /**
     * Sets curl option.
     *
     * @param $name
     * @param $value
     */
    private function setOption($name, $value): void
    {
        curl_setopt($this->request, $name, $value);
    }
}
