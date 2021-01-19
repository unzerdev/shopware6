<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\CancelService;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use RuntimeException;
use Shopware\Core\Framework\Context;

interface CancelServiceInterface
{
    /**
     * @throws HeidelpayApiException
     * @throws RuntimeException
     */
    public function cancelChargeById(
        string $orderTransactionId,
        string $chargeId,
        float $amountGross,
        Context $context
    ): void;
}
