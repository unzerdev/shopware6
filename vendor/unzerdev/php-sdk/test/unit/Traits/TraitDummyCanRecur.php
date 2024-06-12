<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines a dummy implementing CanRecur trait.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Traits;

use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Traits\CanRecur;

class TraitDummyCanRecur extends BasePaymentType
{
    use CanRecur;

    public function getId(): ?string
    {
        return 'myId';
    }
}
