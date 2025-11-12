<?php
/**
 * Http adapters to be used by this api have to implement this interface.
 *
 * @link  https://docs.unzer.com/docs/php-sdk/
 *
 */

namespace UnzerSDK\Adapter;

use UnzerSDK\Exceptions\UnzerApiException;

interface HttpAdapterInterface
{
    public const REQUEST_DELETE = 'DELETE';
    public const REQUEST_GET = 'GET';
    public const REQUEST_PATCH = 'PATCH';
    public const REQUEST_POST = 'POST';
    public const REQUEST_PUT = 'PUT';

    /**
     * Initializes the request.
     *
     * @param string      $url        The full url to connect to.
     * @param string|null $payload    Json encoded payload string.
     * @param string      $httpMethod The Http method to perform.
     */
    public function init(string $url, ?string $payload = null, string $httpMethod = HttpAdapterInterface::REQUEST_GET): void;

    /**
     * Executes the request and returns the response.
     *
     * @return string|null
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     */
    public function execute(): ?string;

    /**
     * Returns the Http code of the response.
     *
     * @return string
     */
    public function getResponseCode(): string;

    /**
     * Closes the connection of the request.
     */
    public function close(): void;

    /**
     * Sets the headers for the request.
     * Expects an associative array with $key being the header name and $value being the header value.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers): void;

    /**
     * Sets the user Agent.
     *
     * @param $userAgent
     */
    public function setUserAgent($userAgent): void;
}
