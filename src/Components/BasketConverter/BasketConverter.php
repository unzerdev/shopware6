<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\BasketConverter;

class BasketConverter implements BasketConverterInterface
{    
    public function populateDeprecatedVariables(array $basket): array
    {
        foreach ($basket['basketItems'] as &$item) {
            $item = $this->updateBasketItem($item, $item['vat']);
        }

        return $basket;
    }

    private function updateBasketItem(array $item, float $vat): array
    {
        $vat = $vat / 100;

        if ($item['type'] === 'voucher') {
            $item['amountDiscount'] = $item['amountDiscountPerUnitGross'] * $item['quantity'];
        }
        
        $item['amountPerUnit'] = $item['amountPerUnitGross'];
        $item['amountGross'] = $item['amountPerUnitGross'] * $item['quantity'];
        $item['amountVat'] = round((float) ($item['amountGross'] / (1 + $vat)) * $vat, 4);
        $item['amountNet'] = round((float) $item['amountGross'] / (1 + $vat), 4);

        return $item;
    }
}
