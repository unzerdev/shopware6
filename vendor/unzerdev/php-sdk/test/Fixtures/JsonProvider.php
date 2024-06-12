<?php
/*
 *  Provide Json strings to for unit tests.
 *
 *  @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\Fixtures;

class JsonProvider
{
    private static string $baseDir = __DIR__ . '/jsonData/';

    /**
     * @throws \Exception
     */
    public static function getJsonFromFile(string $path): string
    {
        $filepath = self::$baseDir . $path;
        $filepath = str_replace(['/'], DIRECTORY_SEPARATOR, $filepath);

        if (file_exists($filepath)) {
            return file_get_contents($filepath);
        }

        throw new \Exception('File could not be read.');
    }
}
