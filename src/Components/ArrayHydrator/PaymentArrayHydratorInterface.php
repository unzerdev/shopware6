<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ArrayHydrator;

use heidelpayPHP\Resources\Payment;

interface PaymentArrayHydratorInterface
{
    public function hydrateArray(Payment $resource): array;
}
