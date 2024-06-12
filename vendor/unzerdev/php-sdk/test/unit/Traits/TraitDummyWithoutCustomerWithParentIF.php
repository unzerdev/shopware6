<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines a dummy implementing traits without customer dependency and with implementing the parent
 * interface.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Traits;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Unzer;
use UnzerSDK\Interfaces\UnzerParentInterface;
use UnzerSDK\Traits\CanAuthorize;
use UnzerSDK\Traits\CanDirectCharge;
use UnzerSDK\Traits\CanPayout;

class TraitDummyWithoutCustomerWithParentIF implements UnzerParentInterface
{
    use CanAuthorize;
    use CanDirectCharge;
    use CanPayout;

    /**
     * Returns the Unzer root object.
     *
     * @return Unzer
     */
    public function getUnzerObject(): Unzer
    {
        return new Unzer('s-priv-123');
    }

    /**
     * Returns the url string for this resource.
     *
     * @param bool   $appendId
     * @param string $httpMethod
     *
     * @return string
     */
    public function getUri(bool $appendId = true, string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'test/uri/';
    }
}
