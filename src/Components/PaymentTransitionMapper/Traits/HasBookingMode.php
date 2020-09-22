<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper\Traits;

use heidelpayPHP\Resources\Payment;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

trait HasBookingMode
{
    protected function getBookingMode(Payment $paymentObject): string
    {
        $order = $this->getOrderByPayment($paymentObject->getOrderId());

        if (null === $order) {
            return self::DEFAULT_MODE;
        }

        $config = $this->configReader->read($order->getSalesChannelId());

        return $config->get(self::BOOKING_MODE_KEY, self::DEFAULT_MODE);
    }

    protected function getOrderByPayment(?string $orderTransactionId): ?OrderEntity
    {
        if (empty($orderTransactionId)) {
            return null;
        }

        $transaction = $this->getTransactionById($orderTransactionId);

        if (null === $transaction) {
            return null;
        }

        return $transaction->getOrder();
    }

    protected function getTransactionById(string $transactionId): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('order');

        $orderSearchResult = $this->orderTransactionRepository->search($criteria, Context::createDefaultContext());

        return $orderSearchResult->first();
    }
}
