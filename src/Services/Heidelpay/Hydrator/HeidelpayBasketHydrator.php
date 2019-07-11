<?php

namespace HeidelPayment\Services\Heidelpay\Hydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use InvalidArgumentException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class HeidelpayBasketHydrator implements HeidelpayHydratorInterface
{
    public function hydrateObject(
        SalesChannelContext $channelContext,
        ?AsyncPaymentTransactionStruct $transaction = null
    ): AbstractHeidelpayResource {
        if ($transaction === null) {
            throw new InvalidArgumentException('Transaction struct can not be null');
        }
        $amountTotalVat = $transaction->getOrder()->getAmountTotal() - $transaction->getOrder()->getAmountNet();

        $result = (new Basket(
            $transaction->getOrderTransaction()->getId(),
            $transaction->getOrder()->getAmountTotal(),
            $channelContext->getCurrency()->getIsoCode()
        ))->setAmountTotalVat($amountTotalVat);

        foreach ($transaction->getOrder()->getLineItems() as $lineItem) {
            if ($lineItem->getPrice() === null) {
                return new BasketItem($lineItem->getLabel(), $lineItem->getTotalPrice(), $lineItem->getUnitPrice(), $lineItem->getQuantity());
            }

            $amountTax = 0;
            $taxRate   = 0.0;
            foreach ($lineItem->getPrice()->getCalculatedTaxes() as $tax) {
                $amountTax += $tax->getTax();
                $taxRate += $tax->getTaxRate();
            }

            $amountGross = $lineItem->getTotalPrice();
            $amountNet   = $amountGross - $amountTax;

            $basketItem = (new BasketItem(
                $lineItem->getLabel(),
                $amountNet,
                $lineItem->getUnitPrice(),
                $lineItem->getQuantity()
            ))->setVat($taxRate)
                ->setAmountVat($amountTax)
                ->setAmountGross($lineItem->getTotalPrice())
                ->setImageUrl($lineItem->getCover() ? $lineItem->getCover()->getUrl() : null);

            $result->addBasketItem($basketItem);
        }

        return $result;
    }
}
