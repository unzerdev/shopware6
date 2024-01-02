<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class KeyPairContext extends Struct
{
    private string $salesChannelId;

    private PaymentMethodEntity $paymentMethod;

    private CurrencyEntity $currency;

    private ?string $company;

    public function __construct(string $salesChannelId, PaymentMethodEntity $paymentMethod, CurrencyEntity $currency, ?string $company)
    {
        $this->salesChannelId = $salesChannelId;
        $this->paymentMethod  = $paymentMethod;
        $this->currency       = $currency;
        $this->company        = $company;
    }

    public static function createFromSalesChannelContext(SalesChannelContext $salesChannelContext): ?KeyPairContext
    {
        if (!$salesChannelContext->getCustomer() || !$salesChannelContext->getCustomer()->getActiveBillingAddress()) {
            return null;
        }

        return new self(
            $salesChannelContext->getSalesChannelId(),
            $salesChannelContext->getPaymentMethod(),
            $salesChannelContext->getCurrency(),
            $salesChannelContext->getCustomer()->getActiveBillingAddress()->getCompany()
        );
    }

    public static function createFromSalesChannel(?SalesChannelEntity $salesChannel): ?KeyPairContext
    {
        if (!$salesChannel || !$salesChannel->getPaymentMethod() || !$salesChannel->getCurrency()) {
            return null;
        }

        return new self(
            $salesChannel->getId(),
            $salesChannel->getPaymentMethod(),
            $salesChannel->getCurrency(),
            null
        );
    }

    public static function createFromOrderTransaction(OrderTransactionEntity $transaction): ?KeyPairContext
    {
        $order = $transaction->getOrder();

        if (!$order || !$order->getBillingAddress()) {
            return null;
        }

        return new self(
            $order->getSalesChannelId(),
            $transaction->getPaymentMethod(),
            $order->getCurrency(),
            $order->getBillingAddress()->getCompany()
        );
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethod->getId();
    }

    public function getCurrencyIsoCode(): string
    {
        return $this->currency->getIsoCode();
    }

    public function isB2B(): bool
    {
        return !empty($this->company);
    }
}
