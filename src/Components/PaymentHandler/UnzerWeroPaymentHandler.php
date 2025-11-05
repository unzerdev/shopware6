<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\PaymentHandler\Traits\IsBasicPaymentMethodWithBookingMode;
use UnzerSDK\Resources\PaymentTypes\Wero;

class UnzerWeroPaymentHandler extends AbstractUnzerPaymentHandler
{
    use IsBasicPaymentMethodWithBookingMode;

    protected function getUnzerPaymentTypeObject(): Wero
    {
        return new Wero();
    }

    protected function setBookingMode(): void
    {
        $this->bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_WERO, BookingMode::CHARGE);
    }
}
