<?php

namespace UnzerSDK\Validators;

use function count;

/**
 * This provides validation functions concerning private key.
 *
 * @link  https://docs.unzer.com/
 *
 */
class PrivateKeyValidator
{
    /**
     * Returns true if the given private key has a valid format.
     *
     * @param string|null $key
     *
     * @return bool
     */
    public static function validate(?string $key): bool
    {
        $match = [];
        if ($key === null) {
            return false;
        }
        preg_match('/^[sp]-priv-[a-zA-Z0-9]+/', $key, $match);
        return count($match) > 0;
    }
}
