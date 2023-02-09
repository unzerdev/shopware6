<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\BasketConverter;

interface BasketConverterInterface
{
    public function populateDeprecatedVariables(array $basket): array;
}
