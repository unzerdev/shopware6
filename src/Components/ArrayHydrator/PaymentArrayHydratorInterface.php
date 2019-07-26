<?php

namespace HeidelPayment\Components\ArrayHydrator;

use heidelpayPHP\Resources\Payment;

interface PaymentArrayHydratorInterface
{
    public function hydrateArray(Payment $resource): array;
}
