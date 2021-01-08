<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Validator;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use UnzerPayment6\Installer\PaymentInstaller;

interface AutomaticShippingValidatorInterface
{
    public const HANDLED_PAYMENT_METHODS = [
        PaymentInstaller::PAYMENT_ID_INVOICE_SECURED,
    ];

    /**
     * Returns a boolean indicating if the provided order is able to send a shipping call to unzer.
     */
    public function shouldSendAutomaticShipping(OrderEntity $orderEntity, StateMachineStateEntity $deliveryState): bool;
}
