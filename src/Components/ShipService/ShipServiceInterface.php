<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ShipService;

use RuntimeException;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use UnzerSDK\Exceptions\UnzerApiException;

interface ShipServiceInterface
{
    /**
     * @throws UnzerApiException
     * @throws RuntimeException
     * @throws InvalidTransactionException
     */
    public function shipTransaction(string $orderTransactionId, Context $context): array;
}
