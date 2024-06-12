<?php
/**
 * This custom debug handler will echo out debug messages.
 *
 * @link  https://docs.unzer.com/
 *
 */
namespace UnzerSDK\examples;

use UnzerSDK\Interfaces\DebugHandlerInterface;

class ExampleDebugHandler implements DebugHandlerInterface
{
    private const LOG_TYPE_APPEND_TO_FILE = 3;

    /**
     * {@inheritDoc}
     *
     * ATTENTION: Please make sure the destination file is writable.
     */
    public function log(string $message): void
    {
        /** @noinspection ForgottenDebugOutputInspection */
        error_log($message . "\n", self::LOG_TYPE_APPEND_TO_FILE, __DIR__ . '/log/example.log');
    }
}
