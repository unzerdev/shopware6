<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentHandler;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment6\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment6\Components\ResourceHydrator\ResourceHydratorInterface;
use HeidelPayment6\Components\Struct\Configuration;
use HeidelPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use HeidelPayment6\Components\Validator\AutomaticShippingValidatorInterface;
use HeidelPayment6\Installers\CustomFieldInstaller;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\Metadata;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
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

abstract class AbstractHeidelpayHandler implements AsynchronousPaymentHandlerInterface
{
    /** @var AbstractHeidelpayResource|BasePaymentType */
    protected $paymentType;

    /** @var Payment */
    protected $payment;

    /** @var Heidelpay */
    protected $heidelpayClient;

    /** @var Customer */
    // phpstan-ignore-next-line
    protected $heidelpayCustomer;

    /** @var Basket */
    // phpstan-ignore-next-line
    protected $heidelpayBasket;

    /** @var Metadata */
    // phpstan-ignore-next-line
    protected $heidelpayMetadata;

    /** @var Configuration */
    protected $pluginConfig;

    /** @var string */
    protected $heidelpayCustomerId;

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

    /** @var string */
    private $resourceId;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(
        ResourceHydratorInterface $basketHydrator,
        ResourceHydratorInterface $customerHydrator,
        ResourceHydratorInterface $metadataHydrator,
        EntityRepositoryInterface $transactionRepository,
        ConfigReaderInterface $configService,
        TransactionStateHandlerInterface $transactionStateHandler,
        ClientFactoryInterface $clientFactory
    ) {
        $this->basketHydrator          = $basketHydrator;
        $this->customerHydrator        = $customerHydrator;
        $this->metadataHydrator        = $metadataHydrator;
        $this->transactionRepository   = $transactionRepository;
        $this->configReader            = $configService;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->clientFactory           = $clientFactory;
    }

    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        try {
            $this->pluginConfig    = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
            $this->heidelpayClient = $this->clientFactory->createClient($salesChannelContext->getSalesChannel()->getId());

            $this->resourceId          = $dataBag->get('heidelpayResourceId');
            $this->heidelpayCustomerId = $dataBag->get('heidelpayCustomerId');

            $this->heidelpayBasket   = $this->basketHydrator->hydrateObject($salesChannelContext, $transaction);
            $this->heidelpayMetadata = $this->metadataHydrator->hydrateObject($salesChannelContext, $transaction);

            if (!empty($this->heidelpayCustomerId)) {
                $this->heidelpayCustomer = $this->heidelpayClient->fetchCustomer($this->heidelpayCustomerId);
            } else {
                $this->heidelpayCustomer = $this->customerHydrator->hydrateObject($salesChannelContext, $transaction);
            }

            if (!empty($this->resourceId)) {
                $this->paymentType = $this->heidelpayClient->fetchPaymentType($this->resourceId);
            }

            return new RedirectResponse($transaction->getReturnUrl());
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
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
            $this->pluginConfig    = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
            $this->heidelpayClient = $this->clientFactory->createClient($salesChannelContext->getSalesChannel()->getId());

            $this->payment = $this->heidelpayClient->fetchPaymentByOrderId($transaction->getOrderTransaction()->getId());

            $this->transactionStateHandler->transformTransactionState(
            $transaction->getOrderTransaction(),
            $this->payment,
            $salesChannelContext->getContext()
        );

            $shipmentExecuted = !in_array(
                $transaction->getOrderTransaction()->getPaymentMethodId(),
                AutomaticShippingValidatorInterface::HANDLED_PAYMENT_METHODS,
                false
            );

            $this->setCustomFields($transaction, $salesChannelContext, $shipmentExecuted);
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
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
            CustomFieldInstaller::HEIDELPAY_IS_TRANSACTION => true,
            CustomFieldInstaller::HEIDELPAY_IS_SHIPPED     => $shipmentExcecuted,
        ]);

        $update = [
            'id'           => $transaction->getOrderTransaction()->getId(),
            'customFields' => $customFields,
        ];

        $this->transactionRepository->update([$update], $salesChannelContext->getContext());
    }
}
