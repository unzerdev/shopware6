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

        $amountTotalVat = $transaction->getOrder()->getAmountTotal() - $transaction->getOrder()->getAmountNet();

        $heidelBasket = new Basket(
            $transaction->getOrderTransaction()->getId(),
            $transaction->getOrder()->getAmountTotal(),
            $channelContext->getCurrency()->getIsoCode()
        );

        $heidelBasket->setAmountTotalVat($amountTotalVat);

        foreach ($transaction->getOrder()->getLineItems() as $lineItem) {
            if ($lineItem->getPrice() === null) {
                $heidelBasket->addBasketItem(new BasketItem($lineItem->getLabel(), $lineItem->getTotalPrice(), $lineItem->getUnitPrice(), $lineItem->getQuantity()));

                continue;
            }

            $amountTax = 0;
            $taxRate   = 0.0;
            foreach ($lineItem->getPrice()->getCalculatedTaxes() as $tax) {
                $amountTax += $tax->getTax();
                $taxRate += $tax->getTaxRate();
            }

            $amountGross = $lineItem->getTotalPrice();
            $amountNet   = $amountGross - $amountTax;

            $basketItem = new BasketItem(
                $lineItem->getLabel(),
                $amountNet,
                $lineItem->getUnitPrice(),
                $lineItem->getQuantity()
            );

            $basketItem->setVat($taxRate);
            $basketItem->setAmountVat($amountTax);
            $basketItem->setAmountGross($lineItem->getTotalPrice());
            $basketItem->setImageUrl($lineItem->getCover() ? $lineItem->getCover()->getUrl() : null);

            $heidelBasket->addBasketItem($basketItem);
        }

        return $heidelBasket;
    }
}
