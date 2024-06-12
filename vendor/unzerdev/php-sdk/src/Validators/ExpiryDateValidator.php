<?php

namespace UnzerSDK\Validators;

/**
 * This provides validation functions concerning expiry dates.
 *
 * @link  https://docs.unzer.com/
 *
 */
class ExpiryDateValidator
{
    /**
     * Returns true if the given expiry date has a valid format.
     *
     * @param string $expiryDate
     *
     * @return bool
     */
    public static function validate(string $expiryDate): bool
    {
        return preg_match('/^(0[\d]|1[0-2]|[1-9])\/(\d{2}|\d{4})$/', $expiryDate);
    }
}
