<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Event;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Symfony\Contracts\EventDispatcher\Event;

class AutomaticShippingNotificationEvent extends Event
{
    protected OrderEntity $orderEntity;

    protected string $invoiceId;

    protected Context $context;

    public function __construct(OrderEntity $orderEntity, string $invoiceId, Context $context)
    {
        $this->orderEntity = $orderEntity;
        $this->invoiceId   = $invoiceId;
        $this->context     = $context;
    }

    public function getOrderEntity(): OrderEntity
    {
        return $this->orderEntity;
    }

    public function getInvoiceId(): string
    {
        return $this->invoiceId;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
