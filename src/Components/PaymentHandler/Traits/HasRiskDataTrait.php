<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use UnzerPayment6\Installer\CustomFieldInstaller;
use UnzerSDK\Resources\EmbeddedResources\RiskData;

trait HasRiskDataTrait
{
    private function generateRiskDataResource(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $context): ?RiskData
    {
        $fraudPreventionSessionId = $this->fetchFraudPreventionSessionId($transaction, $context);

        if (null === $fraudPreventionSessionId) {
            return null;
        }

        $riskData = new RiskData();
        $riskData->setThreatMetrixId($fraudPreventionSessionId);

        $customer = $context->getCustomer();

        if (null !== $customer) {
            $date = $customer->getCreatedAt() ? $customer->getCreatedAt()->format('Ymd') : null;

            $riskData->setRegistrationLevel($customer->getGuest() ? '0' : '1');
            $riskData->setRegistrationDate($date);
        }

        return $riskData;
    }

    private function fetchFraudPreventionSessionId(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $context): ?string
    {
        $orderTransaction         = $transaction->getOrderTransaction();
        $currentRequest           = $this->getCurrentRequestFromStack($orderTransaction->getId());
        $fraudPreventionSessionId = $currentRequest->get('unzerPaymentFraudPreventionSessionId', '');

        if (empty($fraudPreventionSessionId)) {
            $customFields = $orderTransaction->getCustomFields() ?? [];

            if (!empty($customFields[CustomFieldInstaller::UNZER_PAYMENT_FRAUD_PREVENTION_SESSION_ID])) {
                $fraudPreventionSessionId = $customFields[CustomFieldInstaller::UNZER_PAYMENT_FRAUD_PREVENTION_SESSION_ID];
            }
        }

        if (empty($fraudPreventionSessionId)) {
            return null;
        }

        $this->transactionRepository->upsert([
            [
                'id'           => $orderTransaction->getId(),
                'customFields' => [
                    CustomFieldInstaller::UNZER_PAYMENT_FRAUD_PREVENTION_SESSION_ID => $fraudPreventionSessionId,
                ],
            ],
        ], $context->getContext());

        return $fraudPreventionSessionId;
    }
}
