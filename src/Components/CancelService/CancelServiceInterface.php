<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\CancelService;

use Shopware\Core\Framework\Context;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\Cancellation;

interface CancelServiceInterface
{
    /**
     * @throws UnzerApiException
     * @throws \RuntimeException
     */
    public function cancelChargeById(
        string $orderTransactionId,
        string $chargeId,
        float $amountGross,
        ?string $reasonCode,
        Context $context,
        string $referenceText = ''
    ): Cancellation;

    /**
     * @throws UnzerApiException
     * @throws \RuntimeException
     */
    public function cancelAuthorizationById(
        string $orderTransactionId,
        string $paymentId,
        float $amountGross,
        Context $context
    ): void;

    public function isPaylaterPaymentMethod(string $paymentMethodId): bool;
}
