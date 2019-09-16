<?php

declare(strict_types=1);

namespace HeidelPayment\Components;

use heidelpayPHP\Interfaces\DebugHandlerInterface;
use Psr\Log\LoggerInterface;

class HeidelpayDebugHandler implements DebugHandlerInterface
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function log(string $message): void
    {
        $this->logger->info($message);
    }
}
