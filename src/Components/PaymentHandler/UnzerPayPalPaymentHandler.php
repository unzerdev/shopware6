<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\CustomFieldsHelper\CustomFieldsHelperInterface;
use UnzerPayment6\Components\ExpressCheckout\ExpressCheckoutService;
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
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;

class UnzerPayPalPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanAuthorize;
    use CanCharge;
    use CanRecur;
    use HasDeviceVault;

    /**
     * @var BasePaymentType|Paypal
     */
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
        $currentRequest = $this->getCurrentRequestFromStack($transaction->getOrderTransaction()->getId());
        if ($currentRequest->getSession()->get(ExpressCheckoutService::SESSION_PAYPAL_PAYMENT_ID)) {
            try {
                return $this->payExpress(
                    $currentRequest->getSession()->get(ExpressCheckoutService::SESSION_PAYPAL_PAYMENT_ID),
                    $transaction,
                    $dataBag,
                    $currentRequest,
                    $salesChannelContext
                );
            } catch (\Throwable $e) {
                $currentRequest->getSession()->remove(ExpressCheckoutService::SESSION_PAYPAL_PAYMENT_ID);
                // continue with default paypal flow
            }
        }
        parent::pay($transaction, $dataBag, $salesChannelContext);

        if (!empty($this->paymentType)) {
            // this is from a saved payment device
            try {
                return $this->handleRecurringPayment($transaction, $salesChannelContext);
            } catch (UnzerPaymentProcessException $e) {
                // something went wrong with the API > fall back to the default flow
                $this->paymentType = null;
            }
        }

        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL, BookingMode::CHARGE);

        try {
            $savePaymentDevice = $dataBag->has(self::SAVE_PAYMENT_DEVICE_KEY) && $salesChannelContext->getCustomer() !== null && $salesChannelContext->getCustomer()->getGuest() === false;
            $payPalPaymentType = new Paypal();
            if (!empty($this->unzerCustomer->getEmail())) {
                $payPalPaymentType->setEmail($this->unzerCustomer->getEmail());
            }
            $this->paymentType = $this->unzerClient->createPaymentType($payPalPaymentType);

            if ($savePaymentDevice) {
                $returnUrl = $this->activateRecurring($transaction->getReturnUrl());
                if ($this->recurring !== null && !empty($this->recurring->getRedirectUrl())) {
                    $this->persistPaymentInformation(
                        [
                            CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY => '',
                            $this->sessionPaymentTypeKey => $this->paymentType->getId(),
                            $this->sessionCustomerIdKey => $this->unzerCustomer->getId(),
                            self::SAVE_PAYMENT_DEVICE_KEY => true,
                        ],
                        $transaction->getOrderTransaction()->getId(),
                        $salesChannelContext->getContext()
                    );

                    return new RedirectResponse($returnUrl);
                }
            }

            // at this point we are in the default process (no payment from express, saved device or with the command to save the device)

            $returnUrl = $bookingMode === BookingMode::CHARGE
                ? $this->charge($transaction->getReturnUrl())
                : $this->authorize($transaction->getReturnUrl());

            $this->persistPaymentInformation(
                [
                    $this->sessionIsRecurring => false,
                    $this->sessionPaymentTypeKey => $this->payment->getId(),
                    CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY => $this->payment->getId(),
                ],
                $transaction->getOrderTransaction()->getId(),
                $salesChannelContext->getContext()
            );

            return new RedirectResponse($returnUrl);
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
                    'dataBag' => $dataBag,
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
        $this->pluginConfig = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL, BookingMode::CHARGE);

        $transactionCustomFields = $transaction->getOrderTransaction()->getCustomFields();
        $savePaymentDevice = !empty($transactionCustomFields[self::SAVE_PAYMENT_DEVICE_KEY]);

        $this->unzerClient = $this->clientFactory->createClient(
            KeyPairContext::createFromSalesChannelContext($salesChannelContext)
        );

        if (!$savePaymentDevice) {
            parent::finalize($transaction, $request, $salesChannelContext);

            return;
        }

        // else we have to do the charge from a freshly saved payment device

        if ($transactionCustomFields === null || !\array_key_exists($this->sessionPaymentTypeKey, $transactionCustomFields)) {
            throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), 'missing payment id');
        }

        $this->recur($transaction, $salesChannelContext);

        try {
            /** @phpstan-ignore-next-line */
            $this->paymentType = $this->fetchPaymentByTypeId($transactionCustomFields[$this->sessionPaymentTypeKey]);

            if ($this->paymentType === null) {
                throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), 'missing payment type');
            }

            /** Return urls are needed but are not called */
            $bookingMode === BookingMode::CHARGE
                ? $this->charge('https://not.needed')
                : $this->authorize('https://not.needed');

            if ($salesChannelContext->getCustomer() !== null
                && $salesChannelContext->getCustomer()->getGuest() === false
                && $this->paymentType instanceof Paypal
                && $this->paymentType->getEmail() !== null
            ) {
                $this->saveToDeviceVault(
                    $salesChannelContext->getCustomer(),
                    UnzerPaymentDeviceEntity::DEVICE_TYPE_PAYPAL,
                    $salesChannelContext->getContext()
                );
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
                    'exception' => $apiException,
                ]
            );

            throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), $apiException->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->error(
                \sprintf('Caught a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'exception' => $exception,
                ]
            );

            throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), $exception->getMessage());
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
                    $this->sessionIsRecurring => true,
                    $this->sessionPaymentTypeKey => $this->payment->getId(),
                    CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY => $this->payment->getId(),
                ],
                $transaction->getOrderTransaction()->getId(),
                $salesChannelContext->getContext()
            );

            return new RedirectResponse($returnUrl);
        } catch (UnzerApiException $apiException) {
            $this->logger->error(
                \sprintf('Caught an API exception in %s of %s', __METHOD__, __CLASS__),
                [
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
                    'transaction' => $transaction,
                    'exception' => $exception,
                ]
            );

            throw PaymentException::asyncProcessInterrupted($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }

    private function payExpress(
        string $unzerPaymentId,
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        Request $currentRequest,
        SalesChannelContext $salesChannelContext
    ) {
        $unzerClient = $this->unzerClient = $this->clientFactory->createClientFromSalesChannelContext($salesChannelContext, $currentRequest);
        $payment = $unzerClient->fetchPayment($unzerPaymentId);
        /** @var Basket $unzerBasket */
        $unzerBasket = $this->basketHydrator->hydrateObject($salesChannelContext, $transaction);
        $unzerBasket->setId($payment->getBasket()->getId());
        $unzerBasket->setOrderId($transaction->getOrderTransaction()->getId());
        $unzerClient->updateBasket($unzerBasket);

        $orderTransaction = $this->fetchTransactionById($transaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());
        // this does implicitly update the customer object
        $this->getUnzerCustomer($payment->getCustomer()?->getId() ?? '', $transaction->getOrderTransaction()->getPaymentMethodId(), $orderTransaction, $salesChannelContext);

        if (empty($payment->getCharges())) {
            $authorization = new Authorization(
                $unzerBasket->getTotalValueGross(),
                $unzerBasket->getCurrencyCode(),
                $transaction->getReturnUrl()
            );
            $unzerClient->updateAuthorization($payment->getId(), $authorization);
        } else {
            $charge = new Charge(
                $unzerBasket->getTotalValueGross(),
                $unzerBasket->getCurrencyCode(),
                $transaction->getReturnUrl()
            );
            $unzerClient->updateCharge($payment->getId(), $charge);
        }
        $this->persistPaymentInformation(
            [
                $this->sessionIsRecurring => false,
                $this->sessionPaymentTypeKey => $payment->getPaymentType()->getId(),
                CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY => $payment->getId(),
            ],
            $transaction->getOrderTransaction()->getId(),
            $salesChannelContext->getContext()
        );

        return new RedirectResponse($transaction->getReturnUrl());
    }
}
