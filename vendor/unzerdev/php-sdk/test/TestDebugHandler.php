<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This custom debug handler will echo out debug messages.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test;

use UnzerSDK\Interfaces\DebugHandlerInterface;

class TestDebugHandler implements DebugHandlerInterface
{
    /** @var string $tempLog Stores the log messages until reset via clearTempLog() or echoed out via dumpTempLog(). */
    private $tempLog = '';

    /**
     * {@inheritDoc}
     */
    public function log(string $message): void
    {
        $logMessage = 'Unzer debug message: ' . $message . "\n";

        if (Helper\TestEnvironmentService::isTestLoggingActive()) {
            // Echo log messages directly.
            echo $logMessage;
        } else {
            // Store log to echo it when needed.
            $this->tempLog .= $logMessage;
        }
    }

    /**
     * Clears the temp log.
     */
    public function clearTempLog(): void
    {
        $this->tempLog = '';
    }

    /**
     * Echos the contents of tempLog and clears it afterwards.
     */
    public function dumpTempLog(): void
    {
        echo $this->tempLog;
        $this->clearTempLog();
    }
}
