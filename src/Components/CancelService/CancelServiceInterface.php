<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\CancelService;

use RuntimeException;
use Shopware\Core\Framework\Context;
use UnzerSDK\Exceptions\UnzerApiException;

interface CancelServiceInterface
{
    /**
     * @throws UnzerApiException
     * @throws RuntimeException
     */
    public function cancelChargeById(
        string $orderTransactionId,
        string $chargeId,
        float $amountGross,
        Context $context
    ): void;
}
