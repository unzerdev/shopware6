<?php

declare(strict_types=1);

namespace UnzerPayment6\Components;

use Psr\Log\LoggerInterface;
use UnzerSDK\Interfaces\DebugHandlerInterface;

readonly class UnzerPaymentDebugHandler implements DebugHandlerInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function log(string $message): void
    {
        $this->logger->info($message);
    }
}
