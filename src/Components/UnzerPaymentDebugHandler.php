<?php

declare(strict_types=1);

namespace UnzerPayment6\Components;

use Psr\Log\LoggerInterface;
use UnzerSDK\Interfaces\DebugHandlerInterface;

class UnzerPaymentDebugHandler implements DebugHandlerInterface
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
