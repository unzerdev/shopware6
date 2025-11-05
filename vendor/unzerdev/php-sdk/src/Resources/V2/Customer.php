<?php

namespace UnzerSDK\Resources\V2;

use UnzerSDK\Apis\PaymentApiConfigBearerAuth;
use UnzerSDK\Constants\ApiVersions;
use UnzerSDK\Resources\Customer as CustomerV1;

/**
 *
 * This is a prototype version of the v2 Customer resource.
 *
 * This class represents version 2 of Customer resource in the Unzer API.
 * The version uses bearer authentication for API calls.
 * Make sure to use the same Unzer instance to use the same JWT token across multiple calls.
 * Also, the resource ID incorporates UUID and has a length of 42.
 *
 * @category prototype
 */
class Customer extends CustomerV1
{
    public function getApiVersion(): string
    {
        return ApiVersions::V2;
    }

    public function getApiConfig(): string
    {
        return PaymentApiConfigBearerAuth::class;
    }
}