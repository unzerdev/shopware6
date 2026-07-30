<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;
use UnzerPayment6\UnzerPayment6;
use UnzerSDK\Constants\BasketItemTypes;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;

class BasketResourceHydrator
{
    private const UNDEFINED_SHIPPING_METHOD_NAME = 'UndefinedShippingMethod';

    public function hydrateObject(
        OrderTransactionEntity $orderTransaction
    ): Basket {
        $order = $orderTransaction->getOrder();

        if ($order === null) {
            throw new \InvalidArgumentException('Order can not be null');
        }

        return $this->generateUnzerBasket($orderTransaction);
    }

    protected function generateUnzerBasket(OrderTransactionEntity $orderTransaction): Basket
    {
        $order = $orderTransaction->getOrder();

        /** @var int $currencyPrecision */
        $currencyPrecision = $order->getCurrency() !== null ? min(
            $order->getCurrency()->getItemRounding()->getDecimals(),
            UnzerPayment6::MAX_DECIMAL_PRECISION
        ) : UnzerPayment6::MAX_DECIMAL_PRECISION;

        $unzerBasket = new Basket();
        $unzerBasket->setOrderId($orderTransaction->getId());
        $unzerBasket->setTotalValueGross(round($order->getAmountTotal(), $currencyPrecision));
        $unzerBasket->setCurrencyCode($order->getCurrency()->getIsoCode());

        if ($order->getLineItems() !== null) {
            $this->hydrateLineItems(
                $order->getLineItems(),
                $unzerBasket,
                $currencyPrecision,
                $order->getTaxStatus()
            );
        }

        $shippingMethodName = self::UNDEFINED_SHIPPING_METHOD_NAME;
        $shippingMethod = $order->getDeliveries()?->first()?->getShippingMethod();
        if ($shippingMethod !== null) {
            $shippingMethodName = $this->getShippingMethodName($shippingMethod);
        }

        $this->hydrateShippingCosts(
            $order,
            $unzerBasket,
            $currencyPrecision,
            $shippingMethodName
        );

        $this->makeBasketValid($unzerBasket, $currencyPrecision);

        return $unzerBasket;
    }

    protected function hydrateLineItems(
        OrderLineItemCollection $lineItemCollection,
        Basket $unzerBasket,
        int $currencyPrecision,
        ?string $taxStatus
    ): void {
        $customProductLabels = $this->mapCustomProductsLabel($lineItemCollection);

        /** @var OrderLineItemEntity $lineItem */
        foreach ($lineItemCollection as $lineItem) {
            if ($this->isCustomProduct($lineItemCollection, $lineItem)) {
                continue;
            }
            $basketItem = new BasketItem();
            $label = $lineItem->getLabel();
            if (!empty($customProductLabels) && \array_key_exists($lineItem->getId(), $customProductLabels)) {
                $label = $customProductLabels[$lineItem->getId()]
                    ? \sprintf('%s: %s', $lineItem->getLabel(), $customProductLabels[$lineItem->getId()])
                    : $lineItem->getLabel();
            }
            $basketItem->setTitle($label);
            $basketItem->setQuantity($lineItem->getQuantity());
            $basketItem->setType($lineItem->getUnitPrice() < 0 ? BasketItemTypes::VOUCHER : BasketItemTypes::GOODS);

            $taxCounter = 0;
            $amountTax = 0.0;
            $taxRate = 0;
            $unitPrice = $lineItem->getUnitPrice();

            if ($lineItem->getPrice() !== null) {
                /** @var CalculatedTax $tax */
                foreach ($lineItem->getPrice()->getCalculatedTaxes() as $tax) {
                    $amountTax += round($tax->getTax(), $currencyPrecision);
                    $taxRate += $tax->getTaxRate();
                    ++$taxCounter;
                }
                $amountGross = round($lineItem->getTotalPrice(), $currencyPrecision);
                if ($taxStatus === CartPrice::TAX_STATE_NET) {
                    $amountGross += $amountTax;
                }
                $unitPrice = $amountGross / $lineItem->getQuantity();
            }

            $basketItem->setVat($taxCounter === 0 ? 0 : $taxRate / $taxCounter);

            $unitPrice = round($unitPrice, $currencyPrecision);
            if ($unitPrice > 0) {
                $basketItem->setAmountPerUnitGross($unitPrice);
            } else {
                $basketItem->setAmountDiscountPerUnitGross(abs($unitPrice));
            }

            if (!$this->isFreeBasketItem($basketItem, $currencyPrecision)) {
                $unzerBasket->addBasketItem($basketItem);
            }
        }
    }

    protected function hydrateShippingCosts(
        OrderEntity $order,
        Basket $basket,
        int $currencyPrecision,
        string $shippingMethodName
    ): void {
        $shippingCosts = $order->getShippingCosts();

        $dispatchBasketItem = new BasketItem();
        $dispatchBasketItem->setType(BasketItemTypes::SHIPMENT);
        $dispatchBasketItem->setTitle($shippingMethodName);
        $dispatchBasketItem->setQuantity($shippingCosts->getQuantity());

        if ($order->getTaxStatus() === CartPrice::TAX_STATE_FREE) {
            $amountPerUnit = round($shippingCosts->getUnitPrice(), $currencyPrecision);
        } else {
            $priceGross = 0.00;
            $taxRate = 0;
            $taxCounter = 0;

            /** @var CalculatedTax $tax */
            foreach ($shippingCosts->getCalculatedTaxes() as $tax) {
                $priceGross += $tax->getPrice();
                $taxRate += $tax->getTaxRate();
                ++$taxCounter;

                if ($order->getTaxStatus() === CartPrice::TAX_STATE_NET) {
                    $priceGross += $tax->getTax();
                }
            }

            $amountPerUnit = round($priceGross, $currencyPrecision);
            $dispatchBasketItem->setVat($taxCounter > 0 ? ($taxRate / $taxCounter) : 0);
        }

        $dispatchBasketItem->setAmountPerUnitGross(round($amountPerUnit, $currencyPrecision));

        if ($this->isFreeBasketItem($dispatchBasketItem, $currencyPrecision)) {
            return;
        }

        $basket->addBasketItem($dispatchBasketItem);
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

    protected function isCustomProduct(
        OrderLineItemCollection $lineItemCollection,
        OrderLineItemEntity $lineItemEntity
    ): bool {
        if (!class_exists(CustomizedProductsCartDataCollector::class)) {
            return false;
        }

        $isCustomProductOption = \in_array(
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

    protected function getShippingMethodName(ShippingMethodEntity $shippingMethod): string
    {
        if (!empty($shippingMethod->getName())) {
            return $shippingMethod->getName();
        }

        if (!empty($shippingMethod->getTranslated())
            && \array_key_exists('name', $shippingMethod->getTranslated())
            && !empty($shippingMethod->getTranslated()['name'])) {
            return $shippingMethod->getTranslated()['name'];
        }

        return self::UNDEFINED_SHIPPING_METHOD_NAME;
    }

    protected function isFreeBasketItem(BasketItem $basketItem, int $currencyPrecision): bool
    {
        if ((int) round($basketItem->getAmountPerUnitGross() * (10 ** $currencyPrecision)) === 0 && (int) round($basketItem->getAmountDiscountPerUnitGross() * (10 ** $currencyPrecision)) === 0) {
            return true;
        }

        return false;
    }

    private function makeBasketValid(Basket $unzerBasket, int $currencyPrecision): void
    {
        $total = $unzerBasket->getTotalValueGross();
        foreach ($unzerBasket->getBasketItems() as $item) {
            $total -= $item->getAmountPerUnitGross() * $item->getQuantity();
            $total += $item->getAmountDiscountPerUnitGross() * $item->getQuantity();
        }
        if (number_format($total, $currencyPrecision) !== number_format(0, $currencyPrecision)) {
            $basketItem = new BasketItem();
            $basketItem->setTitle('Unzer Shortfall');
            $basketItem->setQuantity(1);
            if ($total > 0) {
                $basketItem->setAmountPerUnitGross($total);
                $basketItem->setType(BasketItemTypes::GOODS);
            } else {
                $basketItem->setAmountDiscountPerUnitGross(abs($total));
                $basketItem->setType(BasketItemTypes::VOUCHER);
            }
            $unzerBasket->addBasketItem($basketItem);
        }
    }
}
