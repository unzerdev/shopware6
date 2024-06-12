<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines a dummy implementing traits with customer dependency and without implementing the parent
 * interface.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Traits;

use UnzerSDK\Traits\CanAuthorizeWithCustomer;
use UnzerSDK\Traits\CanDirectChargeWithCustomer;
use UnzerSDK\Traits\CanPayoutWithCustomer;

class TraitDummyWithCustomerWithoutParentIF
{
    use CanAuthorizeWithCustomer;
    use CanDirectChargeWithCustomer;
    use CanPayoutWithCustomer;
}
