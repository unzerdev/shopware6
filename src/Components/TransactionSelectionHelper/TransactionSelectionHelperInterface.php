<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\TransactionSelectionHelper;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

interface TransactionSelectionHelperInterface
{
    public function getBestUnzerTransaction(OrderEntity $orderEntity): ?OrderTransactionEntity;

    public function getLatestTransaction(OrderTransactionCollection $transactions): ?OrderTransactionEntity;
}
