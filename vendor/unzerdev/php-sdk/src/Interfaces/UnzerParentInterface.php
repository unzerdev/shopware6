<?php
/**
 * This interface defines the methods for a parent Unzer object.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Interfaces;

use UnzerSDK\Unzer;
use RuntimeException;
use UnzerSDK\Adapter\HttpAdapterInterface;

interface UnzerParentInterface
{
    /**
     * Returns the Unzer root object.
     *
     * @return Unzer
     *
     * @throws RuntimeException
     */
    public function getUnzerObject(): Unzer;

    /**
     * Returns the url string for this resource.
     *
     * @param bool   $appendId
     * @param string $httpMethod
     *
     * @return string
     */
    public function getUri(bool $appendId = true, string $httpMethod = HttpAdapterInterface::REQUEST_GET): string;
}
