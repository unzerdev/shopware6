<?php

declare(strict_types=1);

namespace HeidelPayment\Components\ResourceHydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use InvalidArgumentException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class BasketResourceHydrator implements ResourceHydratorInterface
{
    public function hydrateObject(
        SalesChannelContext $channelContext,
        ?AsyncPaymentTransactionStruct $transaction = null
    ): AbstractHeidelpayResource {
        if ($transaction === null) {
            throw new InvalidArgumentException('Transaction struct can not be null');
        }

        $currencyPrecision = $transaction->getOrder()->getCurrency() !== null ? $transaction->getOrder()->getCurrency()->getDecimalPrecision() : 4;
        $currencyPrecision = min($currencyPrecision, 4);

        $amountTotalVat = round($transaction->getOrder()->getAmountTotal() - $transaction->getOrder()->getAmountNet(), $currencyPrecision);

        $heidelBasket = new Basket(
            $transaction->getOrderTransaction()->getId(),
            round($transaction->getOrder()->getAmountTotal()),
            $channelContext->getCurrency()->getIsoCode()
        );

        $heidelBasket->setAmountTotalVat($amountTotalVat);

        foreach ($transaction->getOrder()->getLineItems() as $lineItem) {
            if ($lineItem->getPrice() === null) {
                $heidelBasket->addBasketItem(new BasketItem(
                    $lineItem->getLabel(),
                    round($lineItem->getTotalPrice(), $currencyPrecision),
                    round($lineItem->getUnitPrice(), $currencyPrecision),
                    $lineItem->getQuantity())
                );

                continue;
            }

            $amountTax = 0;
            $taxRate   = 0.0;
            foreach ($lineItem->getPrice()->getCalculatedTaxes() as $tax) {
                $amountTax += round($tax->getTax(), $currencyPrecision);
                $taxRate += $tax->getTaxRate();
            }

            $amountGross = $lineItem->getTotalPrice();
            $amountNet   = round($amountGross - $amountTax, $currencyPrecision);

            $basketItem = new BasketItem(
                $lineItem->getLabel(),
                $amountNet,
                round($lineItem->getUnitPrice()),
                $lineItem->getQuantity()
            );

            $basketItem->setVat($taxRate);
            $basketItem->setAmountVat($amountTax);
            $basketItem->setAmountGross(round($lineItem->getTotalPrice(), $currencyPrecision));
            $basketItem->setImageUrl($lineItem->getCover() ? $lineItem->getCover()->getUrl() : null);

            $heidelBasket->addBasketItem($basketItem);
        }

        return $heidelBasket;
    }
}
