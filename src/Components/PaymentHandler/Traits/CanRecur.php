<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use UnzerPayment6\Components\PaymentHandler\AbstractUnzerPaymentHandler;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Unzer;

trait CanRecur
{
    /** @var string */
    protected $sessionIsRecurring = 'UnzerPaymentIsRecurring';

    /** @var string */
    protected $sessionPaymentTypeKey = 'UnzerPaymentTypeId';

    /** @var string */
    protected $sessionCustomerIdKey = 'UnzerPaymentCustomerId';

    /**
     * @throws UnzerApiException
     */
    public function activateRecurring(string $returnUrl): string
    {
        if (!$this instanceof AbstractUnzerPaymentHandler) {
            throw new RuntimeException('Trait can only be used in a payment handler context which extends the AbstractUnzerPaymentHandler class');
        }

        if ($this->paymentType === null) {
            throw new RuntimeException('PaymentType can not be null');
        }

        if (!method_exists($this->paymentType, 'activateRecurring')) {
            throw new RuntimeException('This payment type does not support recurring');
        }

        $this->recurring = $this->paymentType->activateRecurring($returnUrl);

        if ($this->recurring !== null && !empty($this->recurring->getRedirectUrl())) {
            $this->session->set($this->sessionPaymentTypeKey, $this->recurring->getPaymentTypeId());
            $this->session->set($this->sessionCustomerIdKey, $this->unzerCustomerId);

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
        AsyncPaymentTransactionStruct $transaction,
        SalesChannelContext $salesChannelContext
    ): void {
        $orderTransaction = $this->fetchTransactionById($transaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());

        $this->unzerBasket   = $this->basketHydrator->hydrateObject($salesChannelContext, $orderTransaction ?? $transaction);
        $this->unzerMetadata = $this->metadataHydrator->hydrateObject($salesChannelContext, $orderTransaction ?? $transaction);
        $this->unzerCustomer = $this->getUnzerCustomer($this->session->get($this->sessionCustomerIdKey, '') ?? '', $transaction->getOrderTransaction()->getPaymentMethodId(), $salesChannelContext);
    }

    protected function fetchTransactionById(string $transactionId, Context $context): ?OrderTransactionEntity
    {
        $transactionCriteria = new Criteria([$transactionId]);
        $transactionCriteria->addAssociation('order');
        $transactionCriteria->addAssociation('order.currency');
        $transactionCriteria->addAssociation('order.lineItems');
        $transactionCriteria->addAssociation('order.deliveries');

        $transactionSearchResult = $this->transactionRepository->search($transactionCriteria, $context);

        return $transactionSearchResult->first();
    }
}
