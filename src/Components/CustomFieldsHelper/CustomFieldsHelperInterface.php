<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\CustomFieldsHelper;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;

interface CustomFieldsHelperInterface
{
    public function setOrderTransactionCustomFields(OrderTransactionEntity $transaction, Context $context): void;

    public function setOrderTransactionUnzerFlag(OrderTransactionEntity $transaction, Context $context): void;
}
