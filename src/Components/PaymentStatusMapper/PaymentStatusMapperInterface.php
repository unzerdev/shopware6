<?php

namespace HeidelPayment\Components\PaymentStatusMapper;

use heidelpayPHP\Resources\Payment;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

interface PaymentStatusMapperInterface
{
    public function getPaymentStatus(Payment $payment, Context $context): StateMachineStateEntity;
}
