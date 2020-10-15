<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\ResourceHydrator;

use heidelpayPHP\Constants\BasketItemTypes;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use InvalidArgumentException;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
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

        if ($transaction instanceof AsyncPaymentTransactionStruct) {
            $transactionId = $transaction->getOrderTransaction()->getId();
        } else {
            $transactionId = $transaction->getId();
        }

        $amountTotalDiscount = 0;
        $amountTotalGross    = 0;
        $amountTotalVat      = 0;

        $heidelBasket = new Basket(
            $transactionId,
            round($transaction->getOrder()->getAmountTotal(), $currencyPrecision),
            $channelContext->getCurrency()->getIsoCode()
        );

        $heidelBasket->setAmountTotalVat($amountTotalVat);
        $heidelBasket->setAmountTotalDiscount($amountTotalDiscount);

        if (null === $transaction->getOrder()->getLineItems()) {
            return $heidelBasket;
        }

        /** @var OrderLineItemEntity $lineItem */
        foreach ($transaction->getOrder()->getLineItems() as $lineItem) {
            $amountDiscount = 0;
            $amountGross    = 0;

            $type = $lineItem->getType();

            if ($lineItem->getPrice() === null) {
                $heidelBasket->addBasketItem(
                    new BasketItem(
                        $lineItem->getLabel(),
                        round($this->getAmountByItemType($type, $lineItem->getTotalPrice()), $currencyPrecision),
                        round($this->getAmountByItemType($type, $lineItem->getUnitPrice()), $currencyPrecision),
                        $lineItem->getQuantity()
                    )
                );

                continue;
            }

            $amountTax = 0;
            $taxRate   = 0.0;
            foreach ($lineItem->getPrice()->getCalculatedTaxes() as $tax) {
                $amountTax += round($this->getAmountByItemType($type, $tax->getTax()), $currencyPrecision);
                $taxRate += $tax->getTaxRate();
            }

            if ($this->isPromotionLineItemType($type)) {
                $unitPrice      = 0;
                $amountGross    = 0;
                $amountNet      = 0;
                $amountTax      = 0;
                $taxRate        = 0;
                $amountDiscount = round($this->getAmountByItemType($type, $lineItem->getTotalPrice()), $currencyPrecision);
            } else {
                $unitPrice   = round($this->getAmountByItemType($type, $lineItem->getUnitPrice()), $currencyPrecision);
                $amountGross = round($this->getAmountByItemType($type, $lineItem->getTotalPrice()), $currencyPrecision);
                $amountNet   = round($amountGross - $amountTax, $currencyPrecision);

                if ($lineItem->getProduct() !== null) {
                    $product        = $lineItem->getProduct();
                    $amountDiscount = round(($product->getPrice() - $lineItem->getTotalPrice()) * -1, $currencyPrecision);
                } else {
                    $amountDiscount = 0;
                }
            }

            $amountTotalDiscount += $amountDiscount;
            $amountTotalGross += $amountGross;
            $amountTotalVat += $amountTax;

            $basketItem = new BasketItem(
                $lineItem->getLabel(),
                $amountNet,
                $unitPrice,
                $lineItem->getQuantity()
            );

            $basketItem->setVat($taxRate);
            $basketItem->setType($this->getMappedLineItemType($type));
            $basketItem->setAmountVat($amountTax);
            $basketItem->setAmountGross($amountGross);
            $basketItem->setAmountDiscount($amountDiscount);
            $basketItem->setImageUrl($lineItem->getCover() ? $lineItem->getCover()->getUrl() : null);

            $heidelBasket->addBasketItem($basketItem);
        }

        $this->hydrateShippingCosts(
            $transaction,
            $heidelBasket,
            $currencyPrecision,
            $channelContext->getShippingMethod()->getName(),
            $amountTotalDiscount,
            $amountTotalGross,
            $amountTotalVat
        );

        $heidelBasket->setAmountTotalDiscount($amountTotalDiscount);
        $heidelBasket->setAmountTotalGross($amountTotalGross);
        $heidelBasket->setAmountTotalVat($amountTotalVat);

        return $heidelBasket;
    }

    protected function getAmountByItemType(string $type, float $price): float
    {
        if ($this->isPromotionLineItemType($type)) {
            return $price * -1;
        }

        return $price;
    }

    protected function getMappedLineItemType(string $type): string
    {
        if ($this->isPromotionLineItemType($type)) {
            return BasketItemTypes::VOUCHER;
        }

        return BasketItemTypes::GOODS;
    }

    private function isPromotionLineItemType(string $type): bool
    {
        return $type === PromotionProcessor::LINE_ITEM_TYPE;
    }

    /**
     * @param AsyncPaymentTransactionStruct|OrderTransactionEntity $transaction
     */
    private function hydrateShippingCosts(
        $transaction,
        Basket $basket,
        int $currencyPrecision,
        string $shippingMethodName,
        float &$amountTotalDiscount,
        float &$amountTotalGross,
        float &$amountTotalVat
    ): void {
        $shippingCosts = $transaction->getOrder()->getShippingCosts();

        if ($transaction->getOrder()->getTaxStatus() === CartPrice::TAX_STATE_FREE) {
            $dispatchBasketItem = new BasketItem();
            $dispatchBasketItem->setType(BasketItemTypes::SHIPMENT);
            $dispatchBasketItem->setTitle($shippingMethodName);
            $dispatchBasketItem->setAmountGross(round($shippingCosts->getTotalPrice(), $currencyPrecision));
            $dispatchBasketItem->setAmountPerUnit(round($shippingCosts->getUnitPrice(), $currencyPrecision));
            $dispatchBasketItem->setAmountNet(round($shippingCosts->getTotalPrice(), $currencyPrecision));
            $dispatchBasketItem->setQuantity($shippingCosts->getQuantity());

            $amountTotalDiscount += $dispatchBasketItem->getAmountDiscount();
            $amountTotalGross += $dispatchBasketItem->getAmountGross();
            $amountTotalVat += $dispatchBasketItem->getAmountVat();

            $basket->addBasketItem($dispatchBasketItem);

            return;
        }

        foreach ($shippingCosts->getCalculatedTaxes() as $tax) {
            $price = $tax->getPrice();

            if ($transaction->getOrder()->getTaxStatus() === CartPrice::TAX_STATE_NET) {
                $price += $tax->getTax();
            }

            $dispatchBasketItem = new BasketItem();
            $dispatchBasketItem->setType(BasketItemTypes::SHIPMENT);
            $dispatchBasketItem->setTitle($shippingMethodName);
            $dispatchBasketItem->setAmountGross(round($price, $currencyPrecision));
            $dispatchBasketItem->setAmountPerUnit(round($price, $currencyPrecision));
            $dispatchBasketItem->setAmountNet(round($price - $tax->getTax(), $currencyPrecision));
            $dispatchBasketItem->setAmountVat(round($tax->getTax(), $currencyPrecision));
            $dispatchBasketItem->setQuantity($shippingCosts->getQuantity());
            $dispatchBasketItem->setVat($tax->getTaxRate());

            $amountTotalDiscount += $dispatchBasketItem->getAmountDiscount();
            $amountTotalGross += $dispatchBasketItem->getAmountGross();
            $amountTotalVat += $dispatchBasketItem->getAmountVat();

            $basket->addBasketItem($dispatchBasketItem);
        }
    }
}
