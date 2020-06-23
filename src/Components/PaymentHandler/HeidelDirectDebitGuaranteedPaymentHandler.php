<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentHandler;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment6\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment6\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment6\Components\ResourceHydrator\ResourceHydratorInterface;
use HeidelPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class HeidelDirectDebitGuaranteedPaymentHandler extends AbstractHeidelpayHandler
{
    use CanCharge;

    public function __construct(
        ResourceHydratorInterface $basketHydrator,
        ResourceHydratorInterface $customerHydrator,
        ResourceHydratorInterface $metadataHydrator,
        EntityRepositoryInterface $transactionRepository,
        ConfigReaderInterface $configReader,
        TransactionStateHandlerInterface $transactionStateHandler,
        ClientFactoryInterface $clientFactory,
        RequestStack $requestStack
    ) {
        parent::__construct(
            $basketHydrator,
            $customerHydrator,
            $metadataHydrator,
            $transactionRepository,
            $configReader,
            $transactionStateHandler,
            $clientFactory,
            $requestStack
        );
    }

    /**
     * {@inheritdoc}
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        parent::pay($transaction, $dataBag, $salesChannelContext);

        $currentRequest = $this->getCurrentRequestFromStack($transaction->getOrderTransaction()->getId());

        $birthday = $currentRequest->get('heidelpayBirthday', '');

        if ($currentRequest->get('acceptSepaMandate', 'off') !== 'on') {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'SEPA direct debit mandate has not been accepted by the customer.');
        }

        try {
            $this->heidelpayCustomer->setBirthDate($birthday);
            $this->heidelpayCustomer = $this->heidelpayClient->createOrUpdateCustomer($this->heidelpayCustomer);

            $returnUrl = $this->charge($transaction->getReturnUrl());

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        }
    }
}
