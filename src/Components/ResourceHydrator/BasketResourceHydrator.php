<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\ResourceHydrator;

use heidelpayPHP\Constants\BasketItemTypes;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use InvalidArgumentException;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class BasketResourceHydrator implements ResourceHydratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrateObject(
        SalesChannelContext $channelContext,
        $transaction = null
    ): AbstractHeidelpayResource {
        if (!($transaction instanceof AsyncPaymentTransactionStruct) && !($transaction instanceof OrderTransactionEntity)) {
            throw new InvalidArgumentException('Transaction struct can not be null');
        }

        $currencyPrecision = $transaction->getOrder()->getCurrency() !== null ? $transaction->getOrder()->getCurrency()->getDecimalPrecision() : 4;
        $currencyPrecision = min($currencyPrecision, 4);

        $amountTotalVat = round($transaction->getOrder()->getAmountTotal() - $transaction->getOrder()->getAmountNet(), $currencyPrecision);

        if ($transaction instanceof AsyncPaymentTransactionStruct) {
            $transactionId = $transaction->getOrderTransaction()->getId();
        } else {
            $transactionId = $transaction->getId();
        }

        $heidelBasket = new Basket(
            $transactionId,
            round($transaction->getOrder()->getAmountTotal(), $currencyPrecision),
            $channelContext->getCurrency()->getIsoCode()
        );

        $heidelBasket->setAmountTotalVat($amountTotalVat);

        if (null === $transaction->getOrder()->getLineItems()) {
            return $heidelBasket;
        }

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

        $this->hydrateShippingCosts($transaction, $heidelBasket, $currencyPrecision, $channelContext);

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

    /**
     * @param AsyncPaymentTransactionStruct|OrderTransactionEntity $transaction
     */
    private function hydrateShippingCosts($transaction, Basket $basket, int $currencyPrecision, SalesChannelContext $channelContext): void
    {
        $shippingCosts = $transaction->getOrder()->getShippingCosts();

        if ($transaction->getOrder()->getTaxStatus() === CartPrice::TAX_STATE_FREE) {
            $dispatchBasketItem = new BasketItem();
            $dispatchBasketItem->setType(BasketItemTypes::SHIPMENT);
            $dispatchBasketItem->setTitle($channelContext->getShippingMethod()->getName());
            $dispatchBasketItem->setAmountGross(round($shippingCosts->getTotalPrice(), $currencyPrecision));
            $dispatchBasketItem->setAmountPerUnit(round($shippingCosts->getUnitPrice(), $currencyPrecision));
            $dispatchBasketItem->setAmountNet(round($shippingCosts->getTotalPrice(), $currencyPrecision));
            $dispatchBasketItem->setQuantity($shippingCosts->getQuantity());

            $basket->addBasketItem($dispatchBasketItem);
        }

        foreach ($shippingCosts->getCalculatedTaxes() as $tax) {
            $totalAmount = $tax->getPrice();
            $unitPrice   = $tax->getPrice();

            if ($transaction->getOrder()->getTaxStatus() === CartPrice::TAX_STATE_NET) {
                $totalAmount += $tax->getTax();
                $unitPrice += $tax->getTax();
            }

            $dispatchBasketItem = new BasketItem();
            $dispatchBasketItem->setType(BasketItemTypes::SHIPMENT);
            $dispatchBasketItem->setTitle($channelContext->getShippingMethod()->getName());
            $dispatchBasketItem->setAmountGross(round($totalAmount, $currencyPrecision));
            $dispatchBasketItem->setAmountPerUnit(round($unitPrice, $currencyPrecision));
            $dispatchBasketItem->setAmountNet(round($totalAmount - $tax->getTax(), $currencyPrecision));
            $dispatchBasketItem->setAmountVat(round($tax->getTax(), $currencyPrecision));
            $dispatchBasketItem->setQuantity($shippingCosts->getQuantity());
            $dispatchBasketItem->setVat($tax->getTaxRate());

            $basket->addBasketItem($dispatchBasketItem);
        }
    }
}
