<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use function array_key_exists;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\CustomFieldsHelper\CustomFieldsHelperInterface;
use UnzerPayment6\Components\PaymentHandler\Exception\UnzerPaymentProcessException;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\CanRecur;
use UnzerPayment6\Components\PaymentHandler\Traits\HasDeviceVault;
use UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator\CustomerResourceHydratorInterface;
use UnzerPayment6\Components\ResourceHydrator\ResourceHydratorInterface;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;
use UnzerPayment6\Installer\CustomFieldInstaller;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Paypal;

/**
 * @property Payment $payment
 */
class UnzerPayPalPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanCharge;
    use CanAuthorize;
    use CanRecur;
    use HasDeviceVault;

    public const REMEMBER_PAYPAL_ACCOUNT_KEY = 'payPalRemember';

    /** @var BasePaymentType|Paypal */
    protected $paymentType;

    public function __construct(
        ResourceHydratorInterface $basketHydrator,
        CustomerResourceHydratorInterface $customerHydrator,
        ResourceHydratorInterface $metadataHydrator,
        EntityRepository $transactionRepository,
        ConfigReaderInterface $configReader,
        TransactionStateHandlerInterface $transactionStateHandler,
        ClientFactoryInterface $clientFactory,
        RequestStack $requestStack,
        LoggerInterface $logger,
        CustomFieldsHelperInterface $customFieldsHelper,
        UnzerPaymentDeviceRepositoryInterface $deviceRepository
    ) {
        parent::__construct(
            $basketHydrator,
            $customerHydrator,
            $metadataHydrator,
            $transactionRepository,
            $configReader,
            $transactionStateHandler,
            $clientFactory,
            $requestStack,
            $logger,
            $customFieldsHelper
        );

        $this->deviceRepository = $deviceRepository;
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

        if (!empty($this->paymentType)) {
            return $this->handleRecurringPayment($transaction, $salesChannelContext);
        }

        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL, BookingMode::CHARGE);

        try {
            if ($this->paymentType === null) {
                $registerAccounts  = $dataBag->has(self::REMEMBER_PAYPAL_ACCOUNT_KEY);
                $payPalPaymentType = new Paypal();

                if (!empty($this->unzerCustomer->getEmail())) {
                    $payPalPaymentType->setEmail($this->unzerCustomer->getEmail());
                }

                $this->paymentType = $this->unzerClient->createPaymentType($payPalPaymentType);

                if ($registerAccounts && $salesChannelContext->getCustomer() !== null && $salesChannelContext->getCustomer()->getGuest() === false) {
                    $returnUrl = $this->activateRecurring($transaction->getReturnUrl());

                    if ($this->recurring !== null && !empty($this->recurring->getRedirectUrl())) {
                        $this->persistPaymentInformation(
                            [
                                CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY => $this->paymentType->getId(),
                                $this->sessionPaymentTypeKey                       => $this->paymentType->getId(),
                                $this->sessionCustomerIdKey                        => $this->unzerCustomer->getId(),
                                self::REMEMBER_PAYPAL_ACCOUNT_KEY                  => true,
                            ],
                            $transaction->getOrderTransaction()->getId(),
                            $salesChannelContext->getContext()
                        );
                    }

                    return new RedirectResponse($returnUrl);
                }
            }

            $returnUrl = $bookingMode === BookingMode::CHARGE
                ? $this->charge($transaction->getReturnUrl())
                : $this->authorize($transaction->getReturnUrl());

            $this->persistPaymentInformation(
                [
                    $this->sessionIsRecurring                          => true,
                    $this->sessionPaymentTypeKey                       => $this->payment->getId(),
                    CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY => $this->payment->getId(),
                ],
                $transaction->getOrderTransaction()->getId(),
                $salesChannelContext->getContext()
            );

            return new RedirectResponse($returnUrl);
        } catch (UnzerApiException $apiException) {
            $this->logger->error(
                sprintf('Caught an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'request'     => $this->getLoggableRequest($currentRequest),
                    'transaction' => $transaction,
                    'exception'   => $apiException,
                ]
            );

            $this->executeFailTransition(
                $transaction->getOrderTransaction()->getId(),
                $salesChannelContext->getContext()
            );

            throw new UnzerPaymentProcessException($transaction->getOrder()->getId(), $transaction->getOrderTransaction()->getId(), $apiException);
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('Caught a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                      'request' => $this->getLoggableRequest($currentRequest),
                    'dataBag'   => $dataBag,
                    'exception' => $exception,
                ]
            );

            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }

    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        $this->pluginConfig = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL, BookingMode::CHARGE);

        $transactionCustomFields = $transaction->getOrderTransaction()->getCustomFields();
        $registerAccounts        = !empty($transactionCustomFields[self::REMEMBER_PAYPAL_ACCOUNT_KEY]);

        $this->unzerClient = $this->clientFactory->createClient(
            KeyPairContext::createFromSalesChannelContext($salesChannelContext)
        );

        if (!$registerAccounts) {
            parent::finalize($transaction, $request, $salesChannelContext);
        }

        if ($transactionCustomFields === null || !array_key_exists(CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY, $transactionCustomFields)) {
            throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), 'missing payment id');
        }

        $this->recur($transaction, $salesChannelContext);

        try {
            if (!($transactionCustomFields[$this->sessionIsRecurring] ?? false)) {
                /** @phpstan-ignore-next-line */
                $this->paymentType = $this->fetchPaymentByTypeId($transactionCustomFields[$this->sessionPaymentTypeKey]);

                if ($this->paymentType === null) {
                    throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), 'missing payment type');
                }

                /** Return urls are needed but are not called */
                $bookingMode === BookingMode::CHARGE
                    ? $this->charge('https://not.needed')
                    : $this->authorize('https://not.needed');

                if ($registerAccounts
                    && $salesChannelContext->getCustomer() !== null
                    && $salesChannelContext->getCustomer()->getGuest() === false
                    && $this->paymentType instanceof PayPal
                    && $this->paymentType->getEmail() !== null
                ) {
                    $this->saveToDeviceVault(
                        $salesChannelContext->getCustomer(),
                        UnzerPaymentDeviceEntity::DEVICE_TYPE_PAYPAL,
                        $salesChannelContext->getContext()
                    );
                }
            } else {
                $this->payment = $this->unzerClient->fetchPayment($transactionCustomFields[CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY]);
            }

            $this->transactionStateHandler->transformTransactionState(
                $transaction->getOrderTransaction()->getId(),
                $this->payment,
                $salesChannelContext->getContext()
            );

            $this->customFieldsHelper->setOrderTransactionCustomFields($transaction->getOrderTransaction(), $salesChannelContext->getContext());
        } catch (UnzerApiException $apiException) {
            $this->logger->error(
                sprintf('Caught an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'exception'   => $apiException,
                ]
            );

            throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), $apiException->getMessage());
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('Caught a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'exception'   => $exception,
                ]
            );

            throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }

    protected function handleRecurringPayment(
        AsyncPaymentTransactionStruct $transaction,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        try {
            $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL, BookingMode::CHARGE);

            $returnUrl = $bookingMode === BookingMode::CHARGE
                ? $this->charge($transaction->getReturnUrl())
                : $this->authorize($transaction->getReturnUrl());

            $this->persistPaymentInformation(
                [
                    $this->sessionIsRecurring                          => true,
                    $this->sessionPaymentTypeKey                       => $this->payment->getId(),
                    CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY => $this->payment->getId(),
                ],
                $transaction->getOrderTransaction()->getId(),
                $salesChannelContext->getContext()
            );

            return new RedirectResponse($returnUrl);
        } catch (UnzerApiException $apiException) {
            $this->logger->error(
                sprintf('Caught an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'exception'   => $apiException,
                ]
            );

            $this->executeFailTransition(
                $transaction->getOrderTransaction()->getId(),
                $salesChannelContext->getContext()
            );

            throw new UnzerPaymentProcessException($transaction->getOrder()->getId(), $transaction->getOrderTransaction()->getId(), $apiException);
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('Caught a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'exception'   => $exception,
                ]
            );

            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }
}
