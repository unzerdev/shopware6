<?php

namespace UnzerSDK\Constants;

/**
 * Allowed capture trigger values for Wero event dependent payment.
 */
class WeroCaptureTriggers
{
    public const SHIPPING = 'SHIPPING';
    public const DELIVERY = 'DELIVERY';
    public const AVAILABILITY = 'AVAILABILITY';
    public const SERVICEFULFILMENT = 'SERVICEFULFILMENT';
    public const OTHER = 'OTHER';
}
