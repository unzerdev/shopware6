<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentHandler\Traits;

use HeidelPayment6\Components\PaymentHandler\AbstractHeidelpayHandler;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait CanRecur
{
    protected $sessionIsRecurring    = 'HeidelPaymentIsReccuring';
    protected $sessionPaymentTypeKey = 'HeidelPaymentTypeId';
    protected $sessionCustomerIdKey  = 'HeidelPaymentCustomerId';

    /**
     * @throws HeidelpayApiException
     */
    public function activateRecurring(string $returnUrl): string
    {
        if (!$this instanceof AbstractHeidelpayHandler) {
            throw new RuntimeException('Trait can only be used in a payment handler context which extends the AbstractHeidelpayHandler class');
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
            $this->session->set($this->sessionCustomerIdKey, $this->heidelpayCustomerId);

            return $this->recurring->getRedirectUrl();
        }

        return $returnUrl;
    }

    /**
     * @throws HeidelpayApiException
     */
    public function fetchPaymentByTypeId(string $paymentTypeId): ?AbstractHeidelpayResource
    {
        if (null === $this->heidelpayClient || !($this->heidelpayClient instanceof Heidelpay)) {
            return null;
        }

        return $this->heidelpayClient->fetchPaymentType($paymentTypeId);
    }

    protected function recur(
        AsyncPaymentTransactionStruct $transaction,
        SalesChannelContext $salesChannelContext
    ): void {
        $orderTransaction = $this->fetchTransactionById($transaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());

        $this->heidelpayBasket   = $this->basketHydrator->hydrateObject($salesChannelContext, $orderTransaction ?? $transaction);
        $this->heidelpayMetadata = $this->metadataHydrator->hydrateObject($salesChannelContext, $orderTransaction ?? $transaction);

        if ($this->session->has($this->sessionCustomerIdKey) && !empty($this->session->get($this->sessionCustomerIdKey))) {
            $this->heidelpayCustomer = $this->heidelpayClient->fetchCustomer($this->session->get($this->sessionCustomerIdKey));
        } else {
            $this->heidelpayCustomer = $this->customerHydrator->hydrateObject($salesChannelContext, $transaction);
        }
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
