<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines a dummy used to test the DebugHandlerInterface.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Services;

use UnzerSDK\Interfaces\DebugHandlerInterface;

class DummyDebugHandler implements DebugHandlerInterface
{
    /**
     * This method will allow custom handling of debug output.
     *
     * @param string $message
     */
    public function log(string $message): void
    {
        // do nothing
    }
}
