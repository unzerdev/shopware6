<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Throwable;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\PaymentHandler\Exception\UnzerPaymentProcessException;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\CanRecur;
use UnzerPayment6\Components\PaymentHandler\Traits\HasDeviceVault;
use UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator\CustomerResourceHydratorInterface;
use UnzerPayment6\Components\ResourceHydrator\ResourceHydratorInterface;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerPayment6\Components\Validator\AutomaticShippingValidatorInterface;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;

class UnzerPayPalPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanCharge;
    use CanAuthorize;
    use CanRecur;
    use HasDeviceVault;

    /** @var null|AbstractHeidelpayResource|BasePaymentType|Paypal */
    protected $paymentType;

    /** @var SessionInterface */
    private $session;

    public function __construct(
        ResourceHydratorInterface $basketHydrator,
        CustomerResourceHydratorInterface $customerHydrator,
        ResourceHydratorInterface $metadataHydrator,
        EntityRepositoryInterface $transactionRepository,
        ConfigReaderInterface $configReader,
        TransactionStateHandlerInterface $transactionStateHandler,
        ClientFactoryInterface $clientFactory,
        RequestStack $requestStack,
        LoggerInterface $logger,
        UnzerPaymentDeviceRepositoryInterface $deviceRepository,
        SessionInterface $session
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
            $logger
        );

        $this->deviceRepository        = $deviceRepository;
        $this->session                 = $session;
        $this->configReader            = $configReader;
        $this->clientFactory           = $clientFactory;
        $this->basketHydrator          = $basketHydrator;
        $this->customerHydrator        = $customerHydrator;
        $this->metadataHydrator        = $metadataHydrator;
        $this->transactionRepository   = $transactionRepository;
        $this->transactionStateHandler = $transactionStateHandler;
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

        $this->clearSpecificSessionStorage();

        if ($currentRequest->get('savedPayPalAccount', false)) {
            return $this->handleRecurringPayment($transaction, $salesChannelContext);
        }

        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL, BookingMode::CHARGE);

        try {
            if ($this->paymentType === null) {
                $registerAccounts  = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_REGISTER_PAYPAL, false);
                $payPalPaymentType = new Paypal();

                if (!empty($this->unzerCustomer->getEmail())) {
                    $payPalPaymentType->setEmail($this->unzerCustomer->getEmail());
                }

                $this->paymentType = $this->unzerClient->createPaymentType($payPalPaymentType);

                if ($registerAccounts) {
                    $returnUrl = $this->activateRecurring($transaction->getReturnUrl());

                    return new RedirectResponse($returnUrl);
                }
            }

            $returnUrl = $bookingMode === BookingMode::CHARGE
                ? $this->charge($transaction->getReturnUrl())
                : $this->authorize($transaction->getReturnUrl());

            $this->session->set($this->sessionIsRecurring, true);
            $this->session->set($this->sessionPaymentTypeKey, $this->payment->getId());

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            $this->logger->error(
                sprintf('Catched an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'dataBag'     => $dataBag,
                    'exception'   => $apiException,
                ]
            );

            $this->executeFailTransition(
                $transaction->getOrderTransaction()->getId(),
                $salesChannelContext->getContext()
            );

            throw new UnzerPaymentProcessException($transaction->getOrder()->getId(), $apiException);
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('Catched a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'dataBag'     => $dataBag,
                    'exception'   => $exception,
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
        $this->unzerClient  = $this->clientFactory->createClient($salesChannelContext->getSalesChannel()->getId());

        $bookingMode      = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL, BookingMode::CHARGE);
        $registerAccounts = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_REGISTER_PAYPAL, false);

        if (!$registerAccounts) {
            parent::finalize($transaction, $request, $salesChannelContext);
        }

        if (!$this->session->has($this->sessionPaymentTypeKey)) {
            throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), 'missing payment id');
        }

        $this->recur($transaction, $salesChannelContext);

        try {
            if (!$this->session->get($this->sessionIsRecurring, false)) {
                $this->paymentType = $this->fetchPaymentByTypeId($this->session->get($this->sessionPaymentTypeKey));

                if ($this->paymentType === null) {
                    throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), 'missing payment type');
                }

                /** Return urls are needed but are not called */
                $bookingMode === BookingMode::CHARGE
                    ? $this->charge('https://not.needed')
                    : $this->authorize('https://not.needed');

                if ($registerAccounts && $salesChannelContext->getCustomer() !== null) {
                    $this->saveToDeviceVault(
                        $salesChannelContext->getCustomer(),
                        UnzerPaymentDeviceEntity::DEVICE_TYPE_PAYPAL,
                        $salesChannelContext->getContext()
                    );
                }
            } else {
                $this->payment = $this->unzerClient->fetchPayment($this->session->get($this->sessionPaymentTypeKey));
            }

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
        } catch (HeidelpayApiException $apiException) {
            $this->logger->error(
                sprintf('Catched an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'exception'   => $apiException,
                ]
            );

            throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), $apiException->getMessage());
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('Catched a generic exception in %s of %s', __METHOD__, __CLASS__),
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
        $currentRequest = $this->getCurrentRequestFromStack($transaction->getOrderTransaction()->getId());

        try {
            $this->paymentType = $this->unzerClient->fetchPaymentType($currentRequest->get('savedPayPalAccount', ''));
            $bookingMode       = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL, BookingMode::CHARGE);

            $returnUrl = $bookingMode === BookingMode::CHARGE
                ? $this->charge($transaction->getReturnUrl())
                : $this->authorize($transaction->getReturnUrl());

            $this->session->set($this->sessionIsRecurring, true);

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            $this->logger->error(
                sprintf('Catched an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'exception'   => $apiException,
                ]
            );

            $this->executeFailTransition(
                $transaction->getOrderTransaction()->getId(),
                $salesChannelContext->getContext()
            );

            throw new UnzerPaymentProcessException($transaction->getOrder()->getId(), $apiException);
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('Catched a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'exception'   => $exception,
                ]
            );

            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }

    protected function clearSpecificSessionStorage(): void
    {
        $this->session->remove($this->sessionIsRecurring);
        $this->session->remove($this->sessionPaymentTypeKey);
        $this->session->remove($this->sessionCustomerIdKey);
    }
}
