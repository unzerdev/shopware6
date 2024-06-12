<?php
/**
 * Use this interface in order to implement a custom handler for debug information.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Interfaces;

interface DebugHandlerInterface
{
    /**
     * This method will allow custom handling of debug output.
     *
     * @param string $message
     */
    public function log(string $message);
}
