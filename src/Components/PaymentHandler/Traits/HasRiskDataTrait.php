<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use UnzerPayment6\Installer\CustomFieldInstaller;
use UnzerSDK\Resources\EmbeddedResources\RiskData;

trait HasRiskDataTrait
{
    private function generateRiskDataResource(OrderTransactionEntity $orderTransaction, Context $context): ?RiskData
    {
        $fraudPreventionSessionId = $this->fetchFraudPreventionSessionId($orderTransaction, $context);

        if ($fraudPreventionSessionId === null) {
            return null;
        }

        $riskData = new RiskData();
        $riskData->setThreatMetrixId($fraudPreventionSessionId);

        $customer = $this->customerRepository->search(
            (new Criteria([$orderTransaction->getOrder()->getOrderCustomer()->getCustomerId()])),
            $context
        )->first();

        if ($customer !== null) {
            $date = $customer->getCreatedAt()?->format('Ymd');

            $riskData->setRegistrationLevel($customer->getGuest() ? '0' : '1');
            $riskData->setRegistrationDate($date);
        }

        return $riskData;
    }

    private function fetchFraudPreventionSessionId(OrderTransactionEntity $orderTransaction, Context $context): ?string
    {
        $currentRequest = $this->getCurrentRequestFromStack($orderTransaction->getId());
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
                'id' => $orderTransaction->getId(),
                'customFields' => [
                    CustomFieldInstaller::UNZER_PAYMENT_FRAUD_PREVENTION_SESSION_ID => $fraudPreventionSessionId,
                ],
            ],
        ], $context);

        return $fraudPreventionSessionId;
    }
}
