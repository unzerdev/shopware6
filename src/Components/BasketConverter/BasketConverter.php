<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\BasketConverter;

use UnzerPayment6\UnzerPayment6;

class BasketConverter implements BasketConverterInterface
{
    public function populateDeprecatedVariables(array $basket): array
    {
        foreach ($basket['basketItems'] as &$item) {
            $item = $this->updateBasketItem($item, $item['vat']);
        }

        unset($item);

        return $basket;
    }

    private function updateBasketItem(array $item, float $vat): array
    {
        $vat = $vat / 100;

        if ($item['type'] === 'voucher') {
            $item['amountDiscount'] = round((float) $item['amountDiscountPerUnitGross'] * (int) $item['quantity'], UnzerPayment6::MAX_DECIMAL_PRECISION);
        }

        $item['amountPerUnit'] = $item['amountPerUnitGross'];
        $item['amountGross']   = $item['amountPerUnitGross'] * $item['quantity'];

        $amountNet = (float) $item['amountGross'] / (1 + $vat);

        $item['amountVat'] = round($amountNet * $vat, UnzerPayment6::MAX_DECIMAL_PRECISION);
        $item['amountNet'] = round($amountNet, UnzerPayment6::MAX_DECIMAL_PRECISION);

        return $item;
    }
}
