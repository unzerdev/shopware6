<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentFrame;

class PaymentFrameFactory implements PaymentFrameFactoryInterface
{
    public function getPaymentFrame(string $paymentMethodId): ?string
    {
        if (!array_key_exists($paymentMethodId, self::DEFAULT_FRAME_MAPPING)) {
            return null;
        }

        return self::DEFAULT_FRAME_MAPPING[$paymentMethodId];
    }
}
