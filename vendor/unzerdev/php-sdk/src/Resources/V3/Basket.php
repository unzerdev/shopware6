<?php

namespace UnzerSDK\Resources\V3;

use UnzerSDK\Apis\PaymentApiConfigBearerAuth;
use UnzerSDK\Constants\ApiVersions;
use UnzerSDK\Resources\Basket as BasketV2;

/**
 * This is a prototype of the v3 Basket resource.
 *
 * This class represents version 3 of Basket resource in the Unzer API.
 * The version uses bearer authentication for API calls.
 * Make sure to use the same Unzer instance to use the same JWT token across multiple calls.
 * Also, the resource ID incorporates UUID and has a length of 42.
 *
 * @category prototype
 */
class Basket extends BasketV2
{
    public function __construct(
        string $orderId = '',
        float  $totalValueGross = 0.0,
        string $currencyCode = 'EUR',
        array  $basketItems = []
    )
    {
        $this->orderId = $orderId;
        $this->setTotalValueGross($totalValueGross);
        $this->currencyCode = $currencyCode;
        $this->setBasketItems($basketItems);
    }

    public function getApiVersion(): string
    {
        return ApiVersions::V3;
    }

    public function getApiConfig(): string
    {
        return PaymentApiConfigBearerAuth::class;
    }
}
