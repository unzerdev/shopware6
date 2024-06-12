<?php

namespace UnzerSDK\Constants;

/**
 * This file contains the different cancel reason codes.
 *
 * @link  https://docs.unzer.com/
 *
 */
class CancelReasonCodes
{
    public const REASON_CODE_CANCEL = 'CANCEL';
    public const REASON_CODE_RETURN = 'RETURN';
    public const REASON_CODE_CREDIT = 'CREDIT';

    public const REASON_CODE_ARRAY = [
        self::REASON_CODE_CANCEL,
        self::REASON_CODE_RETURN,
        self::REASON_CODE_CREDIT
    ];
}
