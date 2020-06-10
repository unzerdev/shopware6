<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\ResourceHydrator;

use heidelpayPHP\Constants\BasketItemTypes;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use InvalidArgumentException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
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
            round($transaction->getOrder()->getAmountTotal(), $currencyPrecision),
            $channelContext->getCurrency()->getIsoCode()
        );

        $heidelBasket->setAmountTotalVat($amountTotalVat);

        foreach ($transaction->getOrder()->getLineItems() as $lineItem) {
            if ($lineItem->getPrice() === null) {
                $heidelBasket->addBasketItem(new BasketItem(
                    $lineItem->getLabel(),
                    round($this->getAmountByItemType($lineItem->getType(), $lineItem->getTotalPrice()), $currencyPrecision),
                    round($this->getAmountByItemType($lineItem->getType(), $lineItem->getUnitPrice()), $currencyPrecision),
                    $lineItem->getQuantity())
                );

                continue;
            }

            $amountTax = 0;
            $taxRate   = 0.0;
            foreach ($lineItem->getPrice()->getCalculatedTaxes() as $tax) {
                $amountTax += round($this->getAmountByItemType($lineItem->getType(), $tax->getTax()), $currencyPrecision);
                $taxRate += $tax->getTaxRate();
            }

            $unitPrice   = round($this->getAmountByItemType($lineItem->getType(), $lineItem->getUnitPrice()), $currencyPrecision);
            $amountGross = round($this->getAmountByItemType($lineItem->getType(), $lineItem->getTotalPrice()), $currencyPrecision);
            $amountNet   = round($amountGross - $amountTax, $currencyPrecision);

            $basketItem = new BasketItem(
                $lineItem->getLabel(),
                $amountNet,
                $unitPrice,
                $lineItem->getQuantity()
            );

            $basketItem->setVat($taxRate);
            $basketItem->setType($this->getMappedLineItemType($lineItem->getType()));
            $basketItem->setAmountVat($amountTax);
            $basketItem->setAmountGross($amountGross);
            $basketItem->setImageUrl($lineItem->getCover() ? $lineItem->getCover()->getUrl() : null);

            $heidelBasket->addBasketItem($basketItem);
        }

        return $heidelBasket;
    }

    protected function getAmountByItemType(string $type, float $price): float
    {
        if ($type === PromotionProcessor::LINE_ITEM_TYPE) {
            return $price * -1;
        }

        return $price;
    }

    protected function getMappedLineItemType(string $type): string
    {
        if ($type === PromotionProcessor::LINE_ITEM_TYPE) {
            return BasketItemTypes::VOUCHER;
        }

        return BasketItemTypes::GOODS;
    }
}
