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
        ?string $reasonCode,
        Context $context
    ): void;

    /**
     * @throws UnzerApiException
     * @throws RuntimeException
     */
    public function cancelAuthorizationById(
        string $orderTransactionId,
        string $authorizationId,
        float $amountGross,
        Context $context
    ): void;
}
