<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\PaymentTransitionMapper\Traits\IsBasicPaymentMethodTransitionMapperWithBookingMode;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Googlepay;

class GooglePayTransitionMapper extends AbstractTransitionMapper
{
    use IsBasicPaymentMethodTransitionMapperWithBookingMode;

    private const BOOKING_MODE_KEY = ConfigReader::CONFIG_KEY_GOOGLE_PAY_BOOKING_MODE;
    private const DEFAULT_MODE = BookingMode::CHARGE;

    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Googlepay;
    }

    protected function getResourceName(): string
    {
        return Googlepay::getResourceName();
    }
}
