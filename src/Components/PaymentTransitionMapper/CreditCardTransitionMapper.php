<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\PaymentTransitionMapper\Traits\IsBasicPaymentMethodTransitionMapperWithBookingMode;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\Clicktopay;

class CreditCardTransitionMapper extends AbstractTransitionMapper
{
    use IsBasicPaymentMethodTransitionMapperWithBookingMode;

    private const BOOKING_MODE_KEY = ConfigReader::CONFIG_KEY_BOOKING_MODE_CARD;
    private const DEFAULT_MODE = BookingMode::CHARGE;

    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Card || $paymentType instanceof Clicktopay;
    }

    protected function getResourceName(): string
    {
        return Card::getResourceName();
    }
}
