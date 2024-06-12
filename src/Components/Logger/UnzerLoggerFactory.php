<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Logger;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

class UnzerLoggerFactory
{
    protected string $rotatingFilePathPattern;

    public function __construct(
        string $rotatingFilePathPattern,
    ) {
        $this->rotatingFilePathPattern = $rotatingFilePathPattern;
    }

    public function createLogger(string $filePrefix, ?int $fileRotationCount = null): LoggerInterface
    {
        $filepath = \sprintf($this->rotatingFilePathPattern, $filePrefix);

        $result = new Logger($filePrefix);
        $result->pushHandler(new RotatingFileHandler($filepath, $fileRotationCount ?? 14));
        $result->pushProcessor(new PsrLogMessageProcessor());

        return $result;
    }
}
