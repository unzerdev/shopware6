<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\PaymentTransitionMapper\Traits\IsBasicPaymentMethodTransitionMapperWithBookingMode;
use UnzerSDK\Resources\PaymentTypes\Applepay;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

class ApplePayTransitionMapper extends AbstractTransitionMapper
{
    use IsBasicPaymentMethodTransitionMapperWithBookingMode;

    private const BOOKING_MODE_KEY = ConfigReader::CONFIG_KEY_BOOKING_MODE_APPLE_PAY;
    private const DEFAULT_MODE = BookingMode::CHARGE;

    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Applepay;
    }

    protected function getResourceName(): string
    {
        return Applepay::getResourceName();
    }
}
