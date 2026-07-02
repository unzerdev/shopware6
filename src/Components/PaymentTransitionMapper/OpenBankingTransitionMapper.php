<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use UnzerPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\OpenbankingPis;

class OpenBankingTransitionMapper extends AbstractTransitionMapper
{
    public function getTargetPaymentStatus(Payment $paymentObject, string $orderTransactionId): string
    {
        try {
            $charges = $paymentObject->getCharges();
            $charge = reset($charges);
            if ($paymentObject->isCompleted() && $charge->isPending()) {
                return StateMachineTransitionActions::ACTION_REOPEN;
            }

            return parent::getTargetPaymentStatus($paymentObject, $orderTransactionId);
        } catch (TransitionMapperException $exception) {
            if ($paymentObject->isPending()) {
                return StateMachineTransitionActions::ACTION_REOPEN;
            }

            throw $exception;
        }
    }

    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof OpenbankingPis;
    }

    protected function getResourceName(): string
    {
        return OpenbankingPis::getResourceName();
    }
}
