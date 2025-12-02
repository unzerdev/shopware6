<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\CustomFieldsHelper\CustomFieldsHelperInterface;
use UnzerPayment6\Components\PaymentHandler\Traits\ExceptionHandler;
use UnzerPayment6\Components\ResourceHydrator\BasketResourceHydrator;
use UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator\CustomerResourceHydratorInterface;
use UnzerPayment6\Components\ResourceHydrator\MetadataResourceHydrator;
use UnzerPayment6\Components\Struct\Configuration;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerPayment6\Components\UnzerUtil\UnzerTransactionUtil;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;
use UnzerPayment6\Installer\CustomFieldInstaller;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\Recurring;
use UnzerSDK\Unzer;

abstract class AbstractUnzerPaymentHandler extends AbstractPaymentHandler
{
    use ExceptionHandler;
    public const SAVE_PAYMENT_DEVICE_KEY = 'save_payment_device';

    protected ?BasePaymentType $paymentType = null;

    protected ?Payment $payment;

    protected Recurring $recurring;

    protected Unzer $unzerClient;

    protected Customer $unzerCustomer;

    protected Basket $unzerBasket;

    protected Metadata $unzerMetadata;

    protected Configuration $pluginConfig;

    protected bool $isExpress = false;

    protected string $bookingMode = BookingMode::CHARGE;

    public function __construct(
        protected readonly BasketResourceHydrator $basketHydrator,
        protected readonly CustomerResourceHydratorInterface $customerHydrator,
        protected readonly MetadataResourceHydrator $metadataHydrator,
        protected readonly EntityRepository $transactionRepository,
        protected readonly ConfigReaderInterface $configReader,
        protected readonly TransactionStateHandlerInterface $transactionStateHandler,
        protected readonly ClientFactoryInterface $clientFactory,
        protected readonly RequestStack $requestStack,
        protected readonly LoggerInterface $logger,
        protected readonly CustomFieldsHelperInterface $customFieldsHelper,
        protected readonly UnzerTransactionUtil $transactionUtil,
        protected readonly EntityRepository $customerRepository,
        protected ?UnzerPaymentDeviceRepositoryInterface $deviceRepository = null
    ) {
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        // TODO
        return false;
    }

    public function pay(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        ?Struct $validateStruct
    ): RedirectResponse {
        $this->logger->debug('Starting pay() base method in ' . static::class);
        $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
        try {
            $salesChannelId = $orderTransaction->getOrder()->getSalesChannelId();

            $this->pluginConfig = $this->configReader->read($salesChannelId);
            $this->unzerClient = $this->clientFactory->createClient(
                KeyPairContext::createFromOrderTransaction($orderTransaction),
                empty($request->getLocale()) ? $request->getDefaultLocale() : $request->getLocale()
            );

            $this->unzerBasket = $this->basketHydrator->hydrateObject($orderTransaction);
            $this->unzerMetadata = $this->metadataHydrator->hydrateObject($context);
            $this->metadataHydrator->setIsExpress($this->unzerMetadata, $this->isExpress);

            $this->unzerCustomer = $this->getUnzerCustomer($request->get('unzerCustomerId', ''), $orderTransaction->getPaymentMethodId(), $orderTransaction, $context);

            $resourceId = $request->get('unzerResourceId', '');

            if (!empty($resourceId)) {
                $this->paymentType = $this->unzerClient->fetchPaymentType($resourceId);
            }

            $this->customFieldsHelper->setOrderTransactionUnzerFlag($orderTransaction, $context);

            return new RedirectResponse($transaction->getReturnUrl());
        } catch (\Throwable $exception) {
            $this->handlePayException($exception, $request, $transaction, $context);
        }
    }

    public function finalize(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context
    ): void {
        $this->logger->debug('Starting finalize() base method in ' . static::class);
        $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
        try {
            $this->pluginConfig = $this->configReader->read($orderTransaction->getOrder()->getSalesChannelId());
            $this->unzerClient = $this->clientFactory->createClient(
                KeyPairContext::createFromOrderTransaction($orderTransaction),
            );
            try {
                $this->payment = $this->unzerClient->fetchPaymentByOrderId(
                    $orderTransaction->getId()
                );
            } catch (UnzerApiException) {
                $paymentId = $orderTransaction->getCustomFields()[CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY] ?? null;
                if ($paymentId) {
                    $this->payment = $this->unzerClient->fetchPayment($paymentId);
                } else {
                    throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), 'no payment found');
                }
            }

            $this->transactionStateHandler->transformTransactionState(
                $orderTransaction->getId(),
                $this->payment,
                $context
            );

            $this->customFieldsHelper->setOrderTransactionCustomFields($orderTransaction, $context);
        } catch (UnzerApiException $apiException) {
            $this->logger->error(
                \sprintf('Caught an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'request' => $this->getLoggableRequest($request),
                    'exception' => $apiException,
                ]
            );

            throw PaymentException::asyncFinalizeInterrupted($orderTransaction->getId(), $apiException->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->error(
                \sprintf('Caught a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'request' => $this->getLoggableRequest($request),
                    'exception' => $exception,
                ]
            );

            throw PaymentException::asyncFinalizeInterrupted($orderTransaction->getId(), $exception->getMessage());
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

    protected function getUnzerCustomer(string $unzerCustomerId, string $paymentMethodId, OrderTransactionEntity $orderTransaction, Context $context): Customer
    {
        $fetchedCustomer = null;
        if (!empty($unzerCustomerId)) {
            try {
                $fetchedCustomer = $this->unzerClient->fetchCustomer($unzerCustomerId);
            } catch (\Throwable) {
                // silent fail
            }
        }
        $orderCustomer = $orderTransaction->getOrder()->getOrderCustomer();
        if (!$fetchedCustomer) {
            $orderBillingAddress = $orderTransaction->getOrder()->getBillingAddress();
            $customerNumber = $this->customerHydrator->getShopCustomerId($orderCustomer->getCustomer(), $orderBillingAddress);
            try {
                $fetchedCustomer = $this->unzerClient->fetchCustomerByExtCustomerId($customerNumber);
            } catch (\Throwable) {
                // silent fail
            }
        }

        if ($fetchedCustomer) {
            $updatedCustomer = $this->customerHydrator->hydrateExistingCustomer($fetchedCustomer, $orderCustomer, $context, $orderTransaction);
            try {
                $updatedCustomer = $this->unzerClient->updateCustomer($updatedCustomer);
            } catch (\Throwable) {
                // silent fail
            }

            return $updatedCustomer;
        }

        return $this->customerHydrator->hydrateObject($paymentMethodId, $orderCustomer, $context, $orderTransaction);
    }

    protected function getLoggableRequest(Request $request): array
    {
        $result = [
            'request-info' => \sprintf('%s %s %s', $request->getMethod(), $request->getRequestUri(), $request->getScheme()) . "\r\n",
            'header' => $request->headers->all(),
            'content' => $request->getContent(),
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
}
