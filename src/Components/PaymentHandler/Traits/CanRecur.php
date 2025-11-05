<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Unzer;

/**
 * @property EntityRepository $transactionRepository
 */
trait CanRecur
{
    /**
     * @var string
     */
    protected $sessionIsRecurring = 'UnzerPaymentIsRecurring';

    /**
     * @var string
     */
    protected $sessionPaymentTypeKey = 'UnzerPaymentTypeId';

    /**
     * @var string
     */
    protected $sessionCustomerIdKey = 'UnzerPaymentCustomerId';

    /**
     * @throws UnzerApiException
     */
    public function activateRecurring(string $returnUrl, ?string $recurrenceType = null): string
    {
        if ($this->paymentType === null) {
            throw new \RuntimeException('PaymentType can not be null');
        }

        if (!method_exists($this->paymentType, 'activateRecurring')) {
            throw new \RuntimeException('This payment type does not support recurring');
        }

        $this->recurring = $this->paymentType->activateRecurring($returnUrl, $recurrenceType);

        if ($this->recurring !== null && !empty($this->recurring->getRedirectUrl())) {
            return $this->recurring->getRedirectUrl();
        }

        return $returnUrl;
    }

    /**
     * @throws UnzerApiException
     */
    public function fetchPaymentByTypeId(string $paymentTypeId): ?AbstractUnzerResource
    {
        if ($this->unzerClient === null || !($this->unzerClient instanceof Unzer)) {
            return null;
        }

        return $this->unzerClient->fetchPaymentType($paymentTypeId);
    }

    protected function recur(
        OrderTransactionEntity $orderTransaction,
        Context $context
    ): void {
        $this->unzerBasket = $this->basketHydrator->hydrateObject($orderTransaction);
        $this->unzerMetadata = $this->metadataHydrator->hydrateObject($context);
        $this->unzerCustomer = $this->getUnzerCustomer(
            unzerCustomerId: $orderTransaction->getCustomFields()[$this->sessionCustomerIdKey] ?? '',
            paymentMethodId: $orderTransaction->getPaymentMethodId(),
            orderTransaction: $orderTransaction,
            context: $context
        );
    }
}
