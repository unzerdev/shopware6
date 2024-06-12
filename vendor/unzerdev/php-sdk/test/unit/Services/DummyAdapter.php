<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines a dummy http adapter used for unit tests.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Services;

use UnzerSDK\Adapter\HttpAdapterInterface;

class DummyAdapter implements HttpAdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public function init(string $url, string $payload = null, string $httpMethod = HttpAdapterInterface::REQUEST_GET): void
    {
        // do nothing
    }

    /**
     * {@inheritDoc}
     */
    public function execute(): string
    {
        return 'responseString';
    }

    /**
     * {@inheritDoc}
     */
    public function getResponseCode(): string
    {
        return 'responseCode';
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        // do nothing
    }

    /**
     * {@inheritDoc}
     */
    public function setHeaders(array $headers): void
    {
        // do nothing
    }

    /**
     * {@inheritDoc}
     */
    public function setUserAgent($userAgent): void
    {
        // do nothing
    }
}
