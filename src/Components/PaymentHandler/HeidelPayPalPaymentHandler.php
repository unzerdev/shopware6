<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentHandler;

use HeidelPayment6\Components\BookingMode;
use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment6\Components\ConfigReader\ConfigReader;
use HeidelPayment6\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use HeidelPayment6\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment6\Components\PaymentHandler\Traits\CanRecur;
use HeidelPayment6\Components\PaymentHandler\Traits\HasDeviceVault;
use HeidelPayment6\Components\ResourceHydrator\ResourceHydratorInterface;
use HeidelPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use HeidelPayment6\Components\Validator\AutomaticShippingValidatorInterface;
use HeidelPayment6\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use HeidelPayment6\DataAbstractionLayer\Repository\PaymentDevice\HeidelpayPaymentDeviceRepositoryInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use RuntimeException;
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

class HeidelPayPalPaymentHandler extends AbstractHeidelpayHandler
{
    use CanCharge;
    use CanAuthorize;
    use CanRecur;
    use HasDeviceVault;

    /** @var null|AbstractHeidelpayResource|BasePaymentType|Paypal */
    protected $paymentType;

    /** @var SessionInterface */
    private $session;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var ResourceHydratorInterface */
    private $basketHydrator;

    /** @var ResourceHydratorInterface */
    private $customerHydrator;

    /** @var ResourceHydratorInterface, */
    private $metadataHydrator;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var TransactionStateHandlerInterface */
    private $transactionStateHandler;

    public function __construct(
        ResourceHydratorInterface $basketHydrator,
        ResourceHydratorInterface $customerHydrator,
        ResourceHydratorInterface $metadataHydrator,
        EntityRepositoryInterface $transactionRepository,
        ConfigReaderInterface $configReader,
        TransactionStateHandlerInterface $transactionStateHandler,
        ClientFactoryInterface $clientFactory,
        RequestStack $requestStack,
        HeidelpayPaymentDeviceRepositoryInterface $deviceRepository,
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
            $requestStack
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
        $this->clearSpecificSessionStorage();

        if ($dataBag->has('savedPayPalAccount')) {
            return $this->handleRecurringPayment($transaction, $dataBag);
        }

        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKINMODE_PAYPAL, BookingMode::CHARGE);

        try {
            if (null === $this->paymentType) {
                $registerAccounts  = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_REGISTER_PAYPAL, false);
                $this->paymentType = $this->heidelpayClient->createPaymentType(new Paypal());

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
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        }
    }

    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        $this->pluginConfig    = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $this->heidelpayClient = $this->clientFactory->createClient($salesChannelContext->getSalesChannel()->getId());

        $bookingMode      = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKINMODE_PAYPAL, BookingMode::CHARGE);
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
                        HeidelpayPaymentDeviceEntity::DEVICE_TYPE_PAYPAL,
                        $salesChannelContext->getContext()
                    );
                }
            } else {
                $this->payment = $this->heidelpayClient->fetchPayment($this->session->get($this->sessionPaymentTypeKey));
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
            dd($apiException);
            throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        } catch (RuntimeException $exception) {
            dd($exception);
            throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }

    protected function handleRecurringPayment(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag
    ): RedirectResponse {
        try {
            $this->paymentType = $this->heidelpayClient->fetchPaymentType($dataBag->get('savedPayPalAccount', ''));
            $bookingMode       = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKINMODE_PAYPAL, BookingMode::CHARGE);

            $returnUrl = $bookingMode === BookingMode::CHARGE
                ? $this->charge($transaction->getReturnUrl())
                : $this->authorize($transaction->getReturnUrl());

            $this->session->set($this->sessionIsRecurring, true);

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        } catch (RuntimeException $exception) {
            throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }

    protected function clearSpecificSessionStorage(): void
    {
        $this->session->remove($this->sessionIsRecurring);
        $this->session->remove($this->sessionPaymentTypeKey);
        $this->session->remove($this->sessionCustomerIdKey);
    }
}
