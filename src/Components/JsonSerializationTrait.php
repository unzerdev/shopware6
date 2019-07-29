<?php

namespace HeidelPayment\Components;

/**
 * A fix for the json_serialize in the Heidelpay SDK. If the precision is higher than allowed by the API (4)
 * it won't accept the call. Use this trait if you want to send a request which includes numeric values to the API.
 */
trait JsonSerializationTrait
{
    /** @var int */
    private $phpPrecision;

    /** @var int */
    private $phpSerializePrecision;

    private function startSerialization(): void
    {
        $this->phpPrecision          = ini_get('precision');
        $this->phpSerializePrecision = ini_get('serialize_precision');

        if (PHP_VERSION_ID >= 70100) {
            ini_set('precision', 17);
            ini_set('serialize_precision', 4);
        }
    }

    private function finishSerialization(): void
    {
        if (PHP_VERSION_ID >= 70100) {
            ini_set('precision', $this->phpPrecision);
            ini_set('serialize_precision', $this->phpSerializePrecision);
        }
    }
}
