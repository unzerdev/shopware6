<?php

namespace UnzerSDK\Services;

use function is_float;
use function strlen;

/**
 * This service provides for functionalities concerning values and their manipulation.
 *
 * @link  https://docs.unzer.com/
 *
 */
class ValueService
{
    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public static function limitFloats($value)
    {
        if (is_float($value)) {
            $value = round($value, 4);
        }
        return $value;
    }

    /**
     * Mask a value.
     *
     * @param        $value
     * @param string $maskSymbol
     *
     * @return string
     */
    public static function maskValue($value, string $maskSymbol = '*'): string
    {
        return substr($value, 0, 6) . str_repeat($maskSymbol, strlen($value) - 10) . substr($value, -4);
    }
}
