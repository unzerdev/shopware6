<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator;

use heidelpayPHP\Constants\BasketItemTypes;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use InvalidArgumentException;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;

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

        $order = $transaction->getOrder();

        if ($order === null) {
            throw new InvalidArgumentException('Order can not be null');
        }

        $currencyPrecision = $order->getCurrency() !== null ? $order->getCurrency()->getDecimalPrecision() : 4;
        /** @var int $currencyPrecision */
        $currencyPrecision = min($currencyPrecision, 4);

        if ($transaction instanceof AsyncPaymentTransactionStruct) {
            $transactionId = $transaction->getOrderTransaction()->getId();
        } else {
            $transactionId = $transaction->getId();
        }

        $amountTotalDiscount = 0;
        $amountTotalGross    = 0;
        $amountTotalVat      = 0;

        $unzerBasket = new Basket(
            $transactionId,
            round($order->getAmountTotal(), $currencyPrecision),
            $channelContext->getCurrency()->getIsoCode()
        );

        $unzerBasket->setAmountTotalVat($amountTotalVat);
        $unzerBasket->setAmountTotalDiscount($amountTotalDiscount);

        $lineItems = $order->getLineItems();

        if ($lineItems === null) {
            return $unzerBasket;
        }

        $this->hydrateLineItems(
            $lineItems,
            $unzerBasket,
            $currencyPrecision,
            $amountTotalDiscount,
            $amountTotalGross,
            $amountTotalVat
        );

        $this->hydrateShippingCosts(
            $transaction,
            $unzerBasket,
            $currencyPrecision,
            $channelContext->getShippingMethod()->getName(),
            $amountTotalDiscount,
            $amountTotalGross,
            $amountTotalVat
        );

        $unzerBasket->setAmountTotalDiscount($amountTotalDiscount);
        $unzerBasket->setAmountTotalGross($amountTotalGross);
        $unzerBasket->setAmountTotalVat($amountTotalVat);

        return $unzerBasket;
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

    protected function hydrateLineItems(
        OrderLineItemCollection $lineItemCollection,
        Basket $unzerBasket,
        int $currencyPrecision,
        int &$amountTotalDiscount,
        int &$amountTotalGross,
        int &$amountTotalVat
    ): void {
        $hasCustomizedProducts = count($transaction->getOrder()->getLineItems()->fmap(function(OrderLineItemEntity $lineItemEntity) {
            return $lineItemEntity->getType() === 'customized-products';
        })) > 0;

        /** @var OrderLineItemEntity $lineItem */
        foreach ($lineItemCollection as $lineItem) {
            $type = $lineItem->getType();

            if ($type === 'customized-products-option' || $type === 'option-values') {
                continue;
            }

            if ($lineItem->getPrice() === null) {
                $unzerBasket->addBasketItem(
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
            /** @var CalculatedTax $tax */
            foreach ($lineItem->getPrice()->getCalculatedTaxes() as $tax) {
                $amountTax += round($this->getAmountByItemType($type, $tax->getTax()), $currencyPrecision);
                $taxRate += $tax->getTaxRate();
            }

            if ($this->isPromotionLineItemType($type)) {
                $unitPrice      = 0;
                $amountGross    = 0;
                $amountNet      = 0;
                $amountDiscount = round(
                    $this->getAmountByItemType($type, $lineItem->getTotalPrice()),
                    $currencyPrecision
                );
            } else {
                // TODO consider other products
                if ($hasCustomizedProducts && $type !== 'customized-products') {
                    $unitPrice = 0;
                    $amountGross = 0;
                    $amountNet = 0;
                    $amountTax = 0;
                    $taxRate = 0;
                    $amountDiscount = 0;
                } else {
                    $unitPrice = round(
                        $this->getAmountByItemType($type, $lineItem->getUnitPrice()),
                        $currencyPrecision
                    );
                    $amountGross = round(
                        $this->getAmountByItemType($type, $lineItem->getTotalPrice()),
                        $currencyPrecision
                    );
                    $amountNet = round($amountGross - $amountTax, $currencyPrecision);

                    $product     = $lineItem->getProduct();
                        if ($product !== null) {
                        $amountDiscount = round(($product->getPrice() - $lineItem->getTotalPrice()) * -1, $currencyPrecision);
                    } else {
                        $amountDiscount = 0;
                    }
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

            $unzerBasket->addBasketItem($basketItem);
        }
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
