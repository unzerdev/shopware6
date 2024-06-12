<?php
/*
 *  Dummy class for AdditionalTransactionData trait.
 *
 *  @link  https://docs.unzer.com/
 *
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */

namespace UnzerSDK\test\unit\Traits;

use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Traits\HasAdditionalTransactionData;

class TraitDummyHasAdditionalTransactionData extends AbstractUnzerResource
{
    use HasAdditionalTransactionData;
}
