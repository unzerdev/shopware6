<?php

namespace UnzerSDK\Resources\TransactionTypes;

use UnzerSDK\Adapter\HttpAdapterInterface;

/**
 * This represents the pre-authorization transaction.
 *
 * @link  https://docs.unzer.com/
 *
 */
class PreAuthorization extends Authorization
{
    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'preauthorize';
    }
}
