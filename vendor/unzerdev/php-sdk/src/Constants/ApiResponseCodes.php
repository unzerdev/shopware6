<?php

namespace UnzerSDK\Constants;

/**
 * This file contains definitions of common response codes.
 *
 * @link  https://docs.unzer.com/
 *
 */
class ApiResponseCodes
{
    // Status codes
    public const API_SUCCESS_REQUEST_PROCESSED_IN_TEST_MODE            = 'API.000.100.112';
    public const API_SUCCESS_CHARGED_AMOUNT_HIGHER_THAN_EXPECTED       = 'API.100.550.340';
    public const API_SUCCESS_CHARGED_AMOUNT_LOWER_THAN_EXPECTED        = 'API.100.550.341';

    public const CORE_TRANSACTION_PENDING                              = 'COR.000.200.000';

    // Errors codes
    public const API_ERROR_GENERAL                                     = 'API.000.000.999';
    public const API_ERROR_PAYMENT_NOT_FOUND                           = 'API.310.100.003';
    public const API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED           = 'API.320.000.004';
    public const API_ERROR_TRANSACTION_CHARGE_NOT_ALLOWED              = 'API.330.000.004';
    public const API_ERROR_TRANSACTION_CANCEL_NOT_ALLOWED              = 'API.340.000.004';
    public const API_ERROR_TRANSACTION_SHIP_NOT_ALLOWED                = 'API.360.000.004';
    public const API_ERROR_SHIPPING_REQUIRES_INVOICE_ID                = 'API.360.100.025';
    public const API_ERROR_CUSTOMER_ID_REQUIRED                        = 'API.320.100.008';
    public const API_ERROR_ORDER_ID_ALREADY_IN_USE                     = 'API.320.200.138';
    public const API_ERROR_RESOURCE_DOES_NOT_BELONG_TO_MERCHANT        = 'API.320.200.145';
    public const API_ERROR_CHARGED_AMOUNT_HIGHER_THAN_EXPECTED         = 'API.330.100.007';
    public const API_ERROR_FACTORING_REQUIRES_CUSTOMER                 = 'API.330.100.008';
    public const API_ERROR_FACTORING_REQUIRES_BASKET                   = 'API.330.100.023';
    public const API_ERROR_ADDRESSES_DO_NOT_MATCH                      = 'API.330.100.106';
    public const API_ERROR_CURRENCY_IS_NOT_SUPPORTED                   = 'API.330.100.202';
    public const API_ERROR_ALREADY_CANCELLED                           = 'API.340.100.014';
    public const API_ERROR_ALREADY_CHARGED_BACK                        = 'API.340.100.015';
    public const API_ERROR_ALREADY_CHARGED                             = 'API.340.100.018';
    public const API_ERROR_CANCEL_REASON_CODE_IS_MISSING               = 'API.340.100.024';
    public const API_ERROR_AMOUNT_IS_MISSING                           = 'API.340.200.130';
    public const API_ERROR_CUSTOMER_DOES_NOT_EXIST                     = 'API.410.100.100';
    public const API_ERROR_CUSTOMER_ID_ALREADY_EXISTS                  = 'API.410.200.010';
    public const API_ERROR_ADDRESS_NAME_TO_LONG                        = 'API.410.200.031';
    public const API_ERROR_CUSTOMER_CAN_NOT_BE_FOUND                   = 'API.500.100.100';
    public const API_ERROR_REQUEST_DATA_IS_INVALID                     = 'API.500.300.999';
    public const API_ERROR_RECURRING_PAYMENT_NOT_SUPPORTED             = 'API.500.550.004';
    public const API_ERROR_WEBHOOK_EVENT_ALREADY_REGISTERED            = 'API.510.310.009';
    public const API_ERROR_WEBHOOK_CAN_NOT_BE_FOUND                    = 'API.510.310.008';
    public const API_ERROR_BASKET_NOT_FOUND                            = 'API.600.410.024';
    public const API_ERROR_BASKET_ITEM_IMAGE_INVALID_URL               = 'API.600.630.004';
    public const API_ERROR_RECURRING_ALREADY_ACTIVE                    = 'API.640.550.006';
    public const API_ERROR_INVALID_KEY                                 = 'API.710.000.002';
    public const API_ERROR_INSUFFICIENT_PERMISSION                     = 'API.710.000.005';
    public const API_ERROR_WRONG_AUTHENTICATION_METHOD                 = 'API.710.000.007';
    public const API_ERROR_FIELD_IS_MISSING                            = 'API.710.200.100';

    public const CORE_ERROR_INVALID_OR_MISSING_LOGIN                   = 'COR.100.300.600';
    public const CORE_INVALID_IP_NUMBER                                = 'COR.100.900.401';
    public const CORE_ERROR_INSURANCE_ALREADY_ACTIVATED                = 'COR.700.400.800';

    public const SDM_ERROR_CURRENT_INSURANCE_EVENT                     = 'SDM.CURRENT_INSURANCE_EVENT';
    public const SDM_ERROR_LIMIT_EXCEEDED                              = 'SDM.LIMIT_EXCEEDED';
    public const SDM_ERROR_NEGATIVE_TRAIT_FOUND                        = 'SDM.NEGATIVE_TRAIT_FOUND';
    public const SDM_ERROR_INCREASED_RISK                              = 'SDM.INCREASED_RISK';
    public const SDM_ERROR_DATA_FORMAT_ERROR                           = 'SDM.DATA_FORMAT_ERROR';
}
