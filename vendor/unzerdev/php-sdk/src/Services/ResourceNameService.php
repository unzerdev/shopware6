<?php

namespace UnzerSDK\Services;

/**
 * This service provides for functionalities concerning resource names.
 *
 * @link  https://docs.unzer.com/
 *
 */
class ResourceNameService
{
    /**
     * Extracts the short name of the given full qualified class name.
     *
     * @param string $classString
     *
     * @return string
     */
    public static function getClassShortName(string $classString): string
    {
        $classNameParts = explode('\\', $classString);
        return end($classNameParts);
    }

    /**
     * Return class short name.
     *
     * @param string $classString
     *
     * @return string
     */
    public static function getClassShortNameKebapCase(string $classString): string
    {
        return self::toKebapCase(self::getClassShortName($classString));
    }

    /**
     * Change camel case string to kebap-case.
     *
     * @param string $str
     *
     * @return string
     */
    private static function toKebapCase(string $str): string
    {
        $kebapCaseString = preg_replace_callback(
            '/([A-Z][a-z])+/',
            static function ($str) {
                return '-' . strtolower($str[0]);
            },
            lcfirst($str)
        );
        return strtolower($kebapCaseString);
    }
}
