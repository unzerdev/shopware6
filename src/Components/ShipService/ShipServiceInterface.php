<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ShipService;

use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use UnzerSDK\Exceptions\UnzerApiException;

interface ShipServiceInterface
{
    /**
     * @throws UnzerApiException
     * @throws \RuntimeException
     * @throws PaymentException
     */
    public function shipTransaction(string $orderTransactionId, Context $context): array;
}
