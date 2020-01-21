<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentFrame;

use HeidelPayment6\Installers\PaymentInstaller;

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

