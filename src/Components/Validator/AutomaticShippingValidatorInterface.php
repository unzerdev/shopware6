<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\Validator;

use HeidelPayment6\Installers\PaymentInstaller;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

interface AutomaticShippingValidatorInterface
{
    public const HANDLED_PAYMENT_METHODS = [
        PaymentInstaller::PAYMENT_ID_INVOICE_FACTORING,
        PaymentInstaller::PAYMENT_ID_INVOICE_GUARANTEED,
    ];

    /**
     * Returns a boolean indicating if the provided order is able to send a shipping call to heidelpay.
     */
    public function shouldSendAutomaticShipping(OrderEntity $orderEntity, StateMachineStateEntity $deliveryState): bool;
}
