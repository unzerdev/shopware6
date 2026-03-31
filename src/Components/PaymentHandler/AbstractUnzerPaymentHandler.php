<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\CustomFieldsHelper\CustomFieldsHelperInterface;
use UnzerPayment6\Components\PaymentHandler\Exception\UnzerPaymentProcessException;
use UnzerPayment6\Components\ResourceHydrator\BasketResourceHydrator;
use UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator\CustomerResourceHydratorInterface;
use UnzerPayment6\Components\ResourceHydrator\MetadataResourceHydrator;
use UnzerPayment6\Components\ResourceHydrator\ResourceHydratorInterface;
use UnzerPayment6\Components\Struct\Configuration;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerPayment6\Installer\CustomFieldInstaller;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\Recurring;
use UnzerSDK\Unzer;

abstract class AbstractUnzerPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    public const SAVE_PAYMENT_DEVICE_KEY = 'save_payment_device';

    /**
     * @var BasePaymentType
     */
    protected $paymentType;

    /**
     * @var Payment|null
     */
    protected $payment;

    /**
     * @var Recurring
     */
    protected $recurring;

    /**
     * @var Unzer
     */
    protected $unzerClient;

    /**
     * @var Customer
     */
    protected $unzerCustomer;

    /**
     * @var Basket
     */
    protected $unzerBasket;

    /**
     * @var Metadata
     */
    protected $unzerMetadata;

    /**
     * @var Configuration
     */
    protected $pluginConfig;

    protected bool $isExpress = false;

    protected string $bookingMode = BookingMode::CHARGE;

    /**
     * @param BasketResourceHydrator $basketHydrator
     * @param MetadataResourceHydrator $metadataHydrator
     */
    public function __construct(
        protected readonly ResourceHydratorInterface $basketHydrator,
        protected readonly CustomerResourceHydratorInterface $customerHydrator,
        protected readonly ResourceHydratorInterface $metadataHydrator,
        protected readonly EntityRepository $transactionRepository,
        protected readonly ConfigReaderInterface $configReader,
        protected readonly TransactionStateHandlerInterface $transactionStateHandler,
        protected readonly ClientFactoryInterface $clientFactory,
        protected readonly RequestStack $requestStack,
        protected readonly LoggerInterface $logger,
        protected readonly CustomFieldsHelperInterface $customFieldsHelper
    ) {
    }

    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $this->logger->debug('Starting pay() base method in ' . static::class);
        $currentRequest = $this->getCurrentRequestFromStack($transaction->getOrderTransaction()->getId());

        try {
            $salesChannelId = $salesChannelContext->getSalesChannel()->getId();

            $this->pluginConfig = $this->configReader->read($salesChannelId);
            $this->unzerClient = $this->clientFactory->createClientFromSalesChannelContext($salesChannelContext, $currentRequest);

            $this->unzerBasket = $this->basketHydrator->hydrateObject($salesChannelContext, $transaction);
            $orderTransaction = $this->fetchTransactionById($transaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());
            $this->unzerMetadata = $this->metadataHydrator->hydrateObject($salesChannelContext, $orderTransaction);
            $this->metadataHydrator->setIsExpress($this->unzerMetadata, $this->isExpress);

            $this->unzerCustomer = $this->getUnzerCustomer($currentRequest->get('unzerCustomerId', ''), $transaction->getOrderTransaction()->getPaymentMethodId(), $transaction->getOrderTransaction(), $salesChannelContext);

            $resourceId = $currentRequest->get('unzerResourceId', '');

            if (!empty($resourceId)) {
                $this->paymentType = $this->unzerClient->fetchPaymentType($resourceId);
            }

            $this->customFieldsHelper->setOrderTransactionUnzerFlag($transaction->getOrderTransaction(), $salesChannelContext->getContext());

            return new RedirectResponse($transaction->getReturnUrl());
        } catch (UnzerApiException $apiException) {
            $this->logger->error(
                \sprintf('Caught an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'request' => $this->getLoggableRequest($currentRequest),
                    'transaction' => $transaction,
                    'exception' => $apiException,
                ]
            );

            $this->executeFailTransition(
                $transaction->getOrderTransaction()->getId(),
                $salesChannelContext->getContext()
            );

            throw new UnzerPaymentProcessException($transaction->getOrder()->getId(), $transaction->getOrderTransaction()->getId(), $apiException);
        } catch (\Throwable $exception) {
            $this->logger->error(
                \sprintf('Caught a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'request' => $this->getLoggableRequest($currentRequest),
                    'transaction' => $transaction,
                    'exception' => $exception,
                ]
            );

            throw PaymentException::asyncProcessInterrupted($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }

    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        $this->logger->debug('Starting finalize() base method in ' . static::class);
        try {
            $this->pluginConfig = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
            $this->unzerClient = $this->clientFactory->createClient(
                KeyPairContext::createFromSalesChannelContext($salesChannelContext)
            );
            try {
                $this->payment = $this->unzerClient->fetchPaymentByOrderId(
                    $transaction->getOrderTransaction()->getId()
                );
            } catch (UnzerApiException) {
                $paymentId = $transaction->getOrderTransaction()->getCustomFields()[CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY] ?? null;
                if ($paymentId) {
                    $this->payment = $this->unzerClient->fetchPayment($paymentId);
                } else {
                    throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), 'no payment found');
                }
            }

            $this->transactionStateHandler->transformTransactionState(
                $transaction->getOrderTransaction()->getId(),
                $this->payment,
                $salesChannelContext->getContext()
            );

            $this->customFieldsHelper->setOrderTransactionCustomFields($transaction->getOrderTransaction(), $salesChannelContext->getContext());
        } catch (UnzerApiException $apiException) {
            $this->logger->error(
                \sprintf('Caught an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'request' => $this->getLoggableRequest($request),
                    'exception' => $apiException,
                ]
            );

            throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), $apiException->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->error(
                \sprintf('Caught a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'request' => $this->getLoggableRequest($request),
                    'exception' => $exception,
                ]
            );

            throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }

    protected function persistPaymentInformation(array $information, string $transactionId, Context $context): void
    {
        $this->transactionRepository->update(
            [
                [
                    'id' => $transactionId,
                    'customFields' => $information,
                ],
            ],
            $context
        );
    }

    protected function getCurrentRequestFromStack(string $orderTransactionId): Request
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        if ($currentRequest === null) {
            throw PaymentException::asyncProcessInterrupted($orderTransactionId, 'No request found');
        }

        return $currentRequest;
    }

    protected function executeFailTransition(string $transactionId, Context $context): void
    {
        $this->transactionStateHandler->fail(
            $transactionId,
            $context
        );
    }

    protected function getUnzerCustomer(string $unzerCustomerId, string $paymentMethodId, OrderTransactionEntity $orderTransaction, SalesChannelContext $salesChannelContext): AbstractUnzerResource
    {
        $customer = $salesChannelContext->getCustomer();
        if (empty($orderTransaction->getOrder())) {
            $orderTransaction = $this->fetchTransactionById($orderTransaction->getId(), $salesChannelContext->getContext());
        }
        $fetchedCustomer = null;

        if (!empty($unzerCustomerId)) {
            try {
                $fetchedCustomer = $this->unzerClient->fetchCustomer($unzerCustomerId);
            } catch (\Throwable $t) {
                // silentfail
            }
        }

        if ($customer && !$fetchedCustomer) {
            $orderBillingAddress = $orderTransaction->getOrder()->getBillingAddress();
            $customerNumber = $this->customerHydrator->getShopCustomerId($customer, $orderBillingAddress);
            try {
                $fetchedCustomer = $this->unzerClient->fetchCustomerByExtCustomerId($customerNumber);
            } catch (\Throwable $t) {
                // silentfail
            }
        }

        if ($fetchedCustomer) {
            /** @var Customer $updatedCustomer */
            $updatedCustomer = $this->customerHydrator->hydrateExistingCustomer($fetchedCustomer, $salesChannelContext, $orderTransaction);

            try {
                $updatedCustomer = $this->unzerClient->updateCustomer($updatedCustomer);
            } catch (\Throwable $t) {
                // silentfail
            }

            return $updatedCustomer;
        }

        return $this->customerHydrator->hydrateObject($paymentMethodId, $salesChannelContext, $orderTransaction);
    }

    protected function getLoggableRequest(Request $request): array
    {
        $result = [
            'request-info' => \sprintf('%s %s %s', $request->getMethod(), $request->getRequestUri(), $request->getScheme()) . "\r\n",
            'header' => $request->headers->all(),
            'content' => $request->getContent(false),
        ];
        $cookies = [];

        foreach ($request->cookies->all() as $cookieKey => $cookieValue) {
            if (\is_array($cookieValue)) {
                $cookies[] = $cookieKey . '=' . json_encode($cookieValue);
            } elseif (\is_scalar($cookieValue)) {
                $cookies[] = $cookieKey . '=' . $cookieValue;
            }
        }

        if (!empty($cookies)) {
            $result['cookie-header'] = 'Cookie: ' . implode('; ', $cookies) . "\r\n";
        }

        return $result;
    }

    protected function fetchTransactionById(string $transactionId, Context $context): ?OrderTransactionEntity
    {
        $transactionCriteria = new Criteria([$transactionId]);
        $transactionCriteria->addAssociations([
            'order',
            'order.billingAddress.country',
            'order.currency',
            'order.documents.documentType',
            'paymentMethod',
            'order.orderCustomer.customer',
            'order.deliveries.shippingMethod.translated',
            'order.deliveries.shippingOrderAddress.country',
            'order.lineItems.product.manufacturer',
            'order.lineItems.cover.url',
            'order.lineItems.calculatedPrices.taxes',
        ]);

        $transactionSearchResult = $this->transactionRepository->search($transactionCriteria, $context);

        return $transactionSearchResult->first();
    }
}
