<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines a dummy implementing HasCancellations and HasPaymentState traits.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Traits;

use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Traits\HasCancellations;
use UnzerSDK\Traits\HasPaymentState;

class TraitDummyHasCancellationsHasPaymentState extends AbstractUnzerResource
{
    use HasCancellations;
    use HasPaymentState;
}
