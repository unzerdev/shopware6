<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator;

use InvalidArgumentException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;
use UnzerPayment6\UnzerPayment6;
use UnzerSDK\Constants\BasketItemTypes;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;

class BasketResourceHydrator implements ResourceHydratorInterface
{
    private const UNDEFINED_SHIPPING_METHOD_NAME = 'UndefinedShippingMethod';

    /**
     * {@inheritdoc}
     */
    public function hydrateObject(
        SalesChannelContext $channelContext,
        $transaction = null
    ): AbstractUnzerResource {
        if (!($transaction instanceof AsyncPaymentTransactionStruct) && !($transaction instanceof OrderTransactionEntity)) {
            throw new InvalidArgumentException('Transaction struct can not be null');
        }

        $order = $transaction->getOrder();

        if ($order === null) {
            throw new InvalidArgumentException('Order can not be null');
        }

        /** @var int $currencyPrecision */
        $currencyPrecision = $order->getCurrency() !== null ? min(
            $order->getCurrency()->getItemRounding()->getDecimals(),
            UnzerPayment6::MAX_DECIMAL_PRECISION
        ) : UnzerPayment6::MAX_DECIMAL_PRECISION;

        if ($transaction instanceof AsyncPaymentTransactionStruct) {
            $transactionId = $transaction->getOrderTransaction()->getId();
        } else {
            $transactionId = $transaction->getId();
        }

        $unzerBasket = new Basket();
        $unzerBasket->setOrderId($transactionId);
        $unzerBasket->setTotalValueGross(round($order->getAmountTotal(), $currencyPrecision));
        $unzerBasket->setCurrencyCode($channelContext->getCurrency()->getIsoCode());

        if ($order->getLineItems() !== null) {
            $this->hydrateLineItems(
                $order->getLineItems(),
                $unzerBasket,
                $currencyPrecision,
                $order->getTaxStatus()
            );
        }

        $this->hydrateShippingCosts(
            $order,
            $unzerBasket,
            $currencyPrecision,
            $this->getShippingMethodName($channelContext->getShippingMethod())
        );

        return $unzerBasket;
    }

    protected function hydrateLineItems(
        OrderLineItemCollection $lineItemCollection,
        Basket $unzerBasket,
        int $currencyPrecision,
        string $taxStatus
    ): void {
        $customProductLabels = $this->mapCustomProductsLabel($lineItemCollection);

        /** @var OrderLineItemEntity $lineItem */
        foreach ($lineItemCollection as $lineItem) {
            if ($lineItem->getProductId() === null && $lineItem->getParentId() === null) {
                continue;
            }
            
            if ($this->isCustomProduct($lineItemCollection, $lineItem)) {
                continue;
            }

            if ($lineItem->getPrice() === null) {
                $basketItem = new BasketItem();
                $basketItem->setTitle($lineItem->getLabel());
                $basketItem->setAmountPerUnitGross(round($this->getAmount($lineItem, $lineItem->getUnitPrice()), $currencyPrecision));
                $basketItem->setQuantity($lineItem->getQuantity());

                if ($this->isFreeBasketItem($basketItem, $currencyPrecision)) {
                    continue;
                }

                $unzerBasket->addBasketItem($basketItem);

                continue;
            }

            $amountTax  = 0.0;
            $taxRate    = 0;
            $taxCounter = 0;
            /** @var CalculatedTax $tax */
            foreach ($lineItem->getPrice()->getCalculatedTaxes() as $tax) {
                $amountTax += round($this->getAmount($lineItem, $tax->getTax()), $currencyPrecision);
                $taxRate += $tax->getTaxRate();
                ++$taxCounter;
            }

            if ($this->isPromotionLineItem($lineItem)) {
                $unitPrice      = 0;
                $amountGross    = 0;
                $amountDiscount = round(
                    $this->getAmount($lineItem, $lineItem->getTotalPrice()),
                    $currencyPrecision
                );

                if ($taxStatus === CartPrice::TAX_STATE_NET) {
                    $amountDiscount += $amountTax;
                }
            } else {
                $unitPrice      = round($this->getAmount($lineItem, $lineItem->getUnitPrice()), $currencyPrecision);
                $amountGross    = round($this->getAmount($lineItem, $lineItem->getTotalPrice()), $currencyPrecision);
                $amountDiscount = 0;

                if ($taxStatus === CartPrice::TAX_STATE_NET) {
                    $amountGross += $amountTax;

                    $unitPrice = round($amountGross / $lineItem->getQuantity(), $currencyPrecision);
                }
            }

            $label = $lineItem->getLabel();

            if (!empty($customProductLabels) && array_key_exists($lineItem->getId(), $customProductLabels)) {
                $label = $customProductLabels[$lineItem->getId()]
                    ? sprintf('%s: %s', $lineItem->getLabel(), $customProductLabels[$lineItem->getId()])
                    : $lineItem->getLabel();
            }

            $basketItem = new BasketItem();
            $basketItem->setTitle($label);
            $basketItem->setAmountPerUnitGross(round($unitPrice, $currencyPrecision));
            $basketItem->setQuantity($lineItem->getQuantity());
            $basketItem->setVat($taxCounter === 0 ? 0 : $taxRate / $taxCounter);
            $basketItem->setType($this->getLineItemType($lineItem));
            $basketItem->setAmountDiscountPerUnitGross(round($amountDiscount, $currencyPrecision));
            $basketItem->setImageUrl($lineItem->getCover() ? $lineItem->getCover()->getUrl() : null);

            if ($this->isFreeBasketItem($basketItem, $currencyPrecision)) {
                continue;
            }

            $unzerBasket->addBasketItem($basketItem);
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

            $amountPerUnit = round($priceGross, $currencyPrecision);
            $dispatchBasketItem->setVat($taxRate / $taxCounter);
        }

        $dispatchBasketItem->setAmountPerUnitGross(round($amountPerUnit, $currencyPrecision));

        if ($this->isFreeBasketItem($dispatchBasketItem, $currencyPrecision)) {
            return;
        }

        $basket->addBasketItem($dispatchBasketItem);
    }

    /**
     * @param LineItem|OrderLineItemEntity $lineItem
     */
    protected function getAmount($lineItem, float $price): float
    {
        if ($price < 0 && $this->isPromotionLineItem($lineItem)) {
            if ($lineItem->getQuantity() > 1) {
                $price = $price / $lineItem->getQuantity();
            }
            return $price * -1;
        }

        return $price;
    }

    /**
     * @param LineItem|OrderLineItemEntity $lineItem
     */
    protected function getLineItemType($lineItem): string
    {
        if ($this->isPromotionLineItem($lineItem)) {
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

    /**
     * @param LineItem|OrderLineItemEntity $lineItem
     */
    protected function isPromotionLineItem($lineItem): bool
    {
        if ($lineItem instanceof OrderLineItemEntity) {
            return !$lineItem->getGood();
        }

        return !$lineItem->isGood();
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

    protected function getShippingMethodName(ShippingMethodEntity $shippingMethod): string
    {
        if (!empty($shippingMethod->getName())) {
            return $shippingMethod->getName();
        }

        if (!empty($shippingMethod->getTranslated())
            && array_key_exists('name', $shippingMethod->getTranslated())
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
}
