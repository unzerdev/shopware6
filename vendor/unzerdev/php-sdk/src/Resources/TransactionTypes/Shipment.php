<?php

namespace UnzerSDK\Resources\TransactionTypes;

use UnzerSDK\Adapter\HttpAdapterInterface;

/**
 * This represents the shipment transaction.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Shipment extends AbstractTransactionType
{
    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'shipments';
    }
}
