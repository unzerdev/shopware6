<?php

namespace UnzerSDK\Validators;

use function count;

/**
 * This provides validation functions concerning public key.
 *
 * @link  https://docs.unzer.com/
 *
 */
class PublicKeyValidator
{
    /**
     * Returns true if the given public key has a valid format.
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
        preg_match('/^[sp]-pub-[a-zA-Z0-9]+/', $key, $match);
        return count($match) > 0;
    }
}
