<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\BackwardsCompatibility;

use Shopware\Core\System\Currency\CurrencyEntity;

class DecimalPrecisionHelper
{
    public static function getPrecision(CurrencyEntity $currencyEntity): int
    {
        // TODO: Remove me if compatibility is at least 6.4.0.0
        if (!method_exists($currencyEntity, 'getItemRounding')) {
            /** @phpstan-ignore-next-line */
            return $currencyEntity->getDecimalPrecision();
        }

        return $currencyEntity->getItemRounding()->getDecimals();
    }
}
