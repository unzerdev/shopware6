<?php

namespace UnzerSDK\Constants;

/**
 * This file contains the different recurrence types.
 *
 * @link  https://docs.unzer.com/
 *
 */
class RecurrenceTypes
{
    /** @var string  Recurring with a defined interval and a defined amount.*/
    public const SCHEDULED = 'scheduled';

    /** @var string  Recurring with an undefined interval and/or an undefined amount.*/
    public const UNSCHEDULED = 'unscheduled';

    /** @var string If the payment type should be used again for future transactions.*/
    public const ONE_CLICK = 'oneclick';
}
