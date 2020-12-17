<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator;

use heidelpayPHP\Constants\BasketItemTypes;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use InvalidArgumentException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;
use UnzerPayment6\UnzerPayment6;

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

        $currencyPrecision = $order->getCurrency() !== null ? min($order->getCurrency()->getDecimalPrecision(), UnzerPayment6::MAX_DECIMAL_PRECISION) : UnzerPayment6::MAX_DECIMAL_PRECISION;
        /** @var int $currencyPrecision */
        $currencyPrecision = min($currencyPrecision, UnzerPayment6::MAX_DECIMAL_PRECISION);

        if ($transaction instanceof AsyncPaymentTransactionStruct) {
            $transactionId = $transaction->getOrderTransaction()->getId();
        } else {
            $transactionId = $transaction->getId();
        }

        $amountTotalDiscount = 0;

        $unzerBasket = new Basket(
            $transactionId,
            round($order->getAmountTotal(), $currencyPrecision),
            $channelContext->getCurrency()->getIsoCode()
        );

        $unzerBasket->setAmountTotalVat($order->getAmountTotal() - $order->getAmountNet());
        $unzerBasket->setAmountTotalDiscount($amountTotalDiscount);

        $lineItems = $order->getLineItems();

        if ($lineItems !== null) {
            $this->hydrateLineItems(
                $lineItems,
                $unzerBasket,
                $currencyPrecision,
                $order->getTaxStatus(),
                $amountTotalDiscount
            );
        }

        $this->hydrateShippingCosts(
            $order,
            $unzerBasket,
            $currencyPrecision,
            $channelContext->getShippingMethod()->getName(),
            $amountTotalDiscount
        );

        $unzerBasket->setAmountTotalDiscount($amountTotalDiscount);

        return $unzerBasket;
    }

    protected function hydrateLineItems(
        OrderLineItemCollection $lineItemCollection,
        Basket $unzerBasket,
        int $currencyPrecision,
        string $taxStatus,
        float &$amountTotalDiscount
    ): void {
        $customProductLabels = $this->mapCustomProductsLabel($lineItemCollection);

        /** @var OrderLineItemEntity $lineItem */
        foreach ($lineItemCollection as $lineItem) {
            $type = $lineItem->getType();

            if ($this->isCustomProduct($lineItemCollection, $lineItem)) {
                continue;
            }

            if ($lineItem->getPrice() === null) {
                $unzerBasket->addBasketItem(
                    new BasketItem(
                        $lineItem->getLabel(),
                        round($this->getAmountByType($type, $lineItem->getTotalPrice()), $currencyPrecision),
                        round($this->getAmountByType($type, $lineItem->getUnitPrice()), $currencyPrecision),
                        $lineItem->getQuantity()
                    )
                );

                continue;
            }

            $amountTax = 0.0;
            $taxRate   = 0;
            $taxAmount = 0;
            /** @var CalculatedTax $tax */
            foreach ($lineItem->getPrice()->getCalculatedTaxes() as $tax) {
                $amountTax += round($this->getAmountByType($type, $tax->getTax()), $currencyPrecision);
                $taxRate += $tax->getTaxRate();
                ++$taxAmount;
            }

            if ($this->isPromotionLineItemType($type)) {
                $unitPrice      = 0;
                $amountGross    = 0;
                $amountNet      = 0;
                $amountDiscount = round(
                    $this->getAmountByType($type, $lineItem->getTotalPrice()),
                    $currencyPrecision
                );

                if ($taxStatus === CartPrice::TAX_STATE_NET) {
                    $amountDiscount += $amountTax;
                }
            } else {
                $unitPrice      = round($this->getAmountByType($type, $lineItem->getUnitPrice()), $currencyPrecision);
                $amountGross    = round($this->getAmountByType($type, $lineItem->getTotalPrice()), $currencyPrecision);
                $amountNet      = round($amountGross - $amountTax, $currencyPrecision);
                $amountDiscount = 0;

                if ($taxStatus === CartPrice::TAX_STATE_NET) {
                    $amountNet = round($amountGross, $currencyPrecision);
                    $amountGross += $amountTax;

                    $unitPrice = $amountGross;
                }
            }

            $amountTotalDiscount += $amountDiscount;
            $label = $lineItem->getLabel();

            if (!empty($customProductLabels) && array_key_exists($lineItem->getId(), $customProductLabels)) {
                $label = $customProductLabels[$lineItem->getId()]
                    ? sprintf('%s: %s', $lineItem->getLabel(), $customProductLabels[$lineItem->getId()])
                    : $lineItem->getLabel();
            }

            $basketItem = new BasketItem(
                $label,
                $amountNet,
                $unitPrice,
                $lineItem->getQuantity()
            );

            $basketItem->setVat($taxAmount === 0 ? 0 : $taxRate / $taxAmount);
            $basketItem->setType($this->getLineItemType($type));
            $basketItem->setAmountVat($amountTax);
            $basketItem->setAmountGross($amountGross);
            $basketItem->setAmountDiscount($amountDiscount);
            $basketItem->setImageUrl($lineItem->getCover() ? $lineItem->getCover()->getUrl() : null);

            $unzerBasket->addBasketItem($basketItem);
        }
    }

    protected function hydrateShippingCosts(
        OrderEntity $order,
        Basket $basket,
        int $currencyPrecision,
        string $shippingMethodName,
        float &$amountTotalDiscount
    ): void {
        $shippingCosts = $order->getShippingCosts();

        $dispatchBasketItem = new BasketItem();
        $dispatchBasketItem->setType(BasketItemTypes::SHIPMENT);
        $dispatchBasketItem->setTitle($shippingMethodName);
        $dispatchBasketItem->setQuantity($shippingCosts->getQuantity());

        if ($order->getTaxStatus() === CartPrice::TAX_STATE_FREE) {
            $amountGross   = round($shippingCosts->getTotalPrice(), $currencyPrecision);
            $amountNet     = round($shippingCosts->getTotalPrice(), $currencyPrecision);
            $amountPerUnit = round($shippingCosts->getUnitPrice(), $currencyPrecision);
        } else {
            $priceGross = 0.00;
            $amountVat  = 0.00;
            $taxRate    = 0;
            $taxCounter = 0;

            /** @var CalculatedTax $tax */
            foreach ($shippingCosts->getCalculatedTaxes() as $tax) {
                $priceGross += $tax->getPrice();
                $amountVat += $tax->getTax();
                $taxRate += $tax->getTaxRate();
                ++$taxCounter;

                if ($order->getTaxStatus() === CartPrice::TAX_STATE_NET) {
                    $priceGross += $tax->getTax();
                }
            }

            $amountGross   = round($priceGross, $currencyPrecision);
            $amountNet     = round($priceGross - $amountVat, $currencyPrecision);
            $amountPerUnit = round($priceGross, $currencyPrecision);

            $dispatchBasketItem->setVat($taxRate / $taxCounter);
            $dispatchBasketItem->setAmountVat(round($amountVat, $currencyPrecision));
        }

        $dispatchBasketItem->setAmountGross($amountGross);
        $dispatchBasketItem->setAmountNet($amountNet);
        $dispatchBasketItem->setAmountPerUnit($amountPerUnit);

        $amountTotalDiscount += $dispatchBasketItem->getAmountDiscount();
        $basket->addBasketItem($dispatchBasketItem);
    }

    protected function getAmountByType(string $type, float $price): float
    {
        if ($this->isPromotionLineItemType($type) && $price < 0) {
            return $price * -1;
        }

        return $price;
    }

    protected function getLineItemType(string $type): string
    {
        if ($this->isPromotionLineItemType($type)) {
            return BasketItemTypes::VOUCHER;
        }

        return BasketItemTypes::GOODS;
    }

    protected function mapCustomProductsLabel(OrderLineItemCollection $lineItemCollection): array
    {
        if (!class_exists(CustomizedProductsCartDataCollector::class)) {
            return [];
        }

        $customProductsLabel = [];

        $productLineItems = $lineItemCollection->filterByType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        /** @var OrderLineItemEntity $lineItem */
        foreach ($productLineItems as $lineItem) {
            if (!$this->isParentCustomProduct($lineItemCollection, $lineItem)) {
                continue;
            }

            $customProductsLabel[$lineItem->getParentId()] = $lineItem->getLabel();
        }

        return $customProductsLabel;
    }

    protected function isPromotionLineItemType(string $type): bool
    {
        return $type === PromotionProcessor::LINE_ITEM_TYPE;
    }

    protected function isCustomProduct(
        OrderLineItemCollection $lineItemCollection,
        OrderLineItemEntity $lineItemEntity
    ): bool {
        if (!class_exists(CustomizedProductsCartDataCollector::class)) {
            return false;
        }

        $isCustomProductOption = in_array(
            $lineItemEntity->getType(),
            [
                CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_OPTION_LINE_ITEM_TYPE,
                CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_OPTION_VALUE_LINE_ITEM_TYPE,
            ],
            true
        );

        return $isCustomProductOption || $this->isParentCustomProduct($lineItemCollection, $lineItemEntity);
    }

    protected function isParentCustomProduct(
        OrderLineItemCollection $lineItemCollection,
        OrderLineItemEntity $lineItemEntity
    ): bool {
        if (!class_exists(CustomizedProductsCartDataCollector::class)) {
            return false;
        }

        $parentLineItem = $lineItemCollection->get($lineItemEntity->getParentId());

        if ($parentLineItem === null) {
            return false;
        }

        return $this->isCustomProductLineItemType($parentLineItem->getType());
    }

    protected function isCustomProductLineItemType(string $type): bool
    {
        if (!class_exists(CustomizedProductsCartDataCollector::class)) {
            return false;
        }

        return $type === CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE;
    }
}
