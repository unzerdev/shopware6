<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines a dummy implementing traits without customer dependency and without implementing the parent
 * interface.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Traits;

use UnzerSDK\Traits\CanAuthorize;
use UnzerSDK\Traits\CanDirectCharge;
use UnzerSDK\Traits\CanPayout;

class TraitDummyWithoutCustomerWithoutParentIF
{
    use CanAuthorize;
    use CanDirectCharge;
    use CanPayout;
}
