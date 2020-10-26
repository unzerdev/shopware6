<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\Metadata;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\Recurring;
use RuntimeException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\ResourceHydrator\ResourceHydratorInterface;
use UnzerPayment6\Components\Struct\Configuration;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerPayment6\Components\Validator\AutomaticShippingValidatorInterface;
use UnzerPayment6\Installers\CustomFieldInstaller;

abstract class AbstractUnzerPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    /** @var AbstractHeidelpayResource|BasePaymentType */
    protected $paymentType;

    /** @var Payment */
    protected $payment;

    /** @var Recurring */
    protected $recurring;

    /** @var Heidelpay */
    protected $unzerClient;

    /** @var Customer */
    protected $unzerCustomer;

    /** @var Basket */
    protected $unzerBasket;

    /** @var Metadata */
    protected $unzerMetadata;

    /** @var Configuration */
    protected $pluginConfig;

    /** @var string */
    protected $unzerCustomerId;

    /** @var ResourceHydratorInterface */
    private $basketHydrator;

    /** @var ResourceHydratorInterface */
    private $customerHydrator;

    /** @var ResourceHydratorInterface */
    private $metadataHydrator;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var TransactionStateHandlerInterface */
    private $transactionStateHandler;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var RequestStack */
    private $requestStack;

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
        $this->basketHydrator          = $basketHydrator;
        $this->customerHydrator        = $customerHydrator;
        $this->metadataHydrator        = $metadataHydrator;
        $this->transactionRepository   = $transactionRepository;
        $this->configReader            = $configReader;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->clientFactory           = $clientFactory;
        $this->requestStack            = $requestStack;
    }

    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $currentRequest = $this->getCurrentRequestFromStack($transaction->getOrderTransaction()->getId());

        try {
            $salesChannelId     = $salesChannelContext->getSalesChannel()->getId();
            $this->pluginConfig = $this->configReader->read($salesChannelId);
            $this->unzerClient  = $this->clientFactory->createClient($salesChannelId);

            $resourceId            = $currentRequest->get('unzerResourceId', '');
            $this->unzerCustomerId = $currentRequest->get('unzerCustomerId', '');
            $this->unzerBasket     = $this->basketHydrator->hydrateObject($salesChannelContext, $transaction);
            $this->unzerMetadata   = $this->metadataHydrator->hydrateObject($salesChannelContext, $transaction);

            if (!empty($this->unzerCustomerId)) {
                $this->unzerCustomer = $this->unzerClient->fetchCustomer($this->unzerCustomerId);
            } else {
                $this->unzerCustomer = $this->customerHydrator->hydrateObject($salesChannelContext, $transaction);
            }

            if (!empty($resourceId)) {
                $this->paymentType = $this->unzerClient->fetchPaymentType($resourceId);
            }

            return new RedirectResponse($transaction->getReturnUrl());
        } catch (HeidelpayApiException $exception) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getClientMessage());
        } catch (RuntimeException $exception) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }

    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        try {
            $this->pluginConfig = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
            $this->unzerClient  = $this->clientFactory->createClient($salesChannelContext->getSalesChannel()->getId());

            $this->payment = $this->unzerClient->fetchPaymentByOrderId($transaction->getOrderTransaction()->getId());

            $this->transactionStateHandler->transformTransactionState(
                $transaction->getOrderTransaction()->getId(),
                $this->payment,
                $salesChannelContext->getContext()
            );

            $shipmentExecuted = !in_array(
                $transaction->getOrderTransaction()->getPaymentMethodId(),
                AutomaticShippingValidatorInterface::HANDLED_PAYMENT_METHODS,
                false
            );

            $this->setCustomFields($transaction, $salesChannelContext, $shipmentExecuted);
        } catch (HeidelpayApiException $exception) {
            throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), $exception->getClientMessage());
        } catch (RuntimeException $exception) {
            throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }

    protected function setCustomFields(
        AsyncPaymentTransactionStruct $transaction,
        SalesChannelContext $salesChannelContext,
        bool $shipmentExcecuted
    ): void {
        $customFields = $transaction->getOrderTransaction()->getCustomFields() ?? [];
        $customFields = array_merge($customFields, [
            CustomFieldInstaller::UNZER_PAYMENT_IS_TRANSACTION => true,
            CustomFieldInstaller::UNZER_PAYMENT_IS_SHIPPED     => $shipmentExcecuted,
        ]);

        $update = [
            'id'           => $transaction->getOrderTransaction()->getId(),
            'customFields' => $customFields,
        ];

        $this->transactionRepository->update([$update], $salesChannelContext->getContext());
    }

    protected function getCurrentRequestFromStack(string $orderTransactionId): Request
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        if ($currentRequest === null) {
            throw new AsyncPaymentProcessException($orderTransactionId, 'No request found');
        }

        return $currentRequest;
    }
}
