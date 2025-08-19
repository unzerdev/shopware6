<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\PaymentHandler\Exception\UnzerPaymentProcessException;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\CanRecur;
use UnzerPayment6\Components\PaymentHandler\Traits\HasDeviceVault;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerPayment6\Installer\CustomFieldInstaller;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Paypal;

/**
 * @property Payment $payment
 */
class UnzerPayPalPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanAuthorize;
    use CanCharge;
    use CanRecur;
    use HasDeviceVault;

    public const REMEMBER_PAYPAL_ACCOUNT_KEY = 'payPalRemember';


    /**
     * {@inheritdoc}
     */
    public function pay(
        Request                  $request,
        PaymentTransactionStruct $transaction,
        Context                  $context,
        ?Struct                  $validateStruct
    ): RedirectResponse
    {
        parent::pay($request, $transaction, $context, $validateStruct);

        if (!empty($this->paymentType)) {
            //return $this->handleRecurringPayment($transaction, $salesChannelContext);
        }

        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL, BookingMode::CHARGE);

        try {
            if (empty($this->paymentType)) {
                $registerAccounts = $request->get(self::REMEMBER_PAYPAL_ACCOUNT_KEY) !== null;
                $payPalPaymentType = new Paypal();

                if (!empty($this->unzerCustomer->getEmail())) {
                    $payPalPaymentType->setEmail($this->unzerCustomer->getEmail());
                }

                $this->paymentType = $this->unzerClient->createPaymentType($payPalPaymentType);

//                if ($registerAccounts && $salesChannelContext->getCustomer() !== null && $salesChannelContext->getCustomer()->getGuest() === false) {
//                    $returnUrl = $this->activateRecurring($transaction->getReturnUrl());
//
//                    if ($this->recurring !== null && !empty($this->recurring->getRedirectUrl())) {
//                        $this->persistPaymentInformation(
//                            [
//                                CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY => $this->paymentType->getId(),
//                                $this->sessionPaymentTypeKey => $this->paymentType->getId(),
//                                $this->sessionCustomerIdKey => $this->unzerCustomer->getId(),
//                                self::REMEMBER_PAYPAL_ACCOUNT_KEY => true,
//                            ],
//                            $transaction->getOrderTransaction()->getId(),
//                            $salesChannelContext->getContext()
//                        );
//                    }
//
//                    return new RedirectResponse($returnUrl);
//                }
            }

            $returnUrl = $bookingMode === BookingMode::CHARGE
                ? $this->charge($transaction->getReturnUrl())
                : $this->authorize($transaction->getReturnUrl());

//            $this->persistPaymentInformation(
//                [
//                    $this->sessionIsRecurring => true,
//                    $this->sessionPaymentTypeKey => $this->payment->getId(),
//                    CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY => $this->payment->getId(),
//                ],
//                $transaction->getOrderTransaction()->getId(),
//                $salesChannelContext->getContext()
//            );

            return new RedirectResponse($returnUrl);
        } catch (\Throwable $exception) {
            $this->handlePayException($exception, $request, $transaction, $context);
        }
    }

//    public function finalize(
//        AsyncPaymentTransactionStruct $transaction,
//        Request                       $request,
//        SalesChannelContext           $salesChannelContext
//    ): void
//    {
//        $this->pluginConfig = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
//
//        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL, BookingMode::CHARGE);
//
//        $transactionCustomFields = $transaction->getOrderTransaction()->getCustomFields();
//        $registerAccounts = !empty($transactionCustomFields[self::REMEMBER_PAYPAL_ACCOUNT_KEY]);
//
//        $this->unzerClient = $this->clientFactory->createClient(
//            KeyPairContext::createFromSalesChannelContext($salesChannelContext)
//        );
//
//        if (!$registerAccounts) {
//            parent::finalize($transaction, $request, $salesChannelContext);
//        }
//
//        if ($transactionCustomFields === null || !\array_key_exists(CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY, $transactionCustomFields)) {
//            throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), 'missing payment id');
//        }
//
//        $this->recur($transaction, $salesChannelContext);
//
//        try {
//            if (!($transactionCustomFields[$this->sessionIsRecurring] ?? false)) {
//                /** @phpstan-ignore-next-line */
//                $this->paymentType = $this->fetchPaymentByTypeId($transactionCustomFields[$this->sessionPaymentTypeKey]);
//
//                if ($this->paymentType === null) {
//                    throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), 'missing payment type');
//                }
//
//                /** Return urls are needed but are not called */
//                $bookingMode === BookingMode::CHARGE
//                    ? $this->charge('https://not.needed')
//                    : $this->authorize('https://not.needed');
//
//                if ($registerAccounts
//                    && $salesChannelContext->getCustomer() !== null
//                    && $salesChannelContext->getCustomer()->getGuest() === false
//                    && $this->paymentType instanceof Paypal
//                    && $this->paymentType->getEmail() !== null
//                ) {
//                    $this->saveToDeviceVault(
//                        $salesChannelContext->getCustomer(),
//                        UnzerPaymentDeviceEntity::DEVICE_TYPE_PAYPAL,
//                        $salesChannelContext->getContext()
//                    );
//                }
//            } else {
//                $this->payment = $this->unzerClient->fetchPayment($transactionCustomFields[CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY]);
//            }
//
//            $this->transactionStateHandler->transformTransactionState(
//                $transaction->getOrderTransaction()->getId(),
//                $this->payment,
//                $salesChannelContext->getContext()
//            );
//
//            $this->customFieldsHelper->setOrderTransactionCustomFields($transaction->getOrderTransaction(), $salesChannelContext->getContext());
//        } catch (UnzerApiException $apiException) {
//            $this->logger->error(
//                \sprintf('Caught an API exception in %s of %s', __METHOD__, __CLASS__),
//                [
//                    'transaction' => $transaction,
//                    'exception' => $apiException,
//                ]
//            );
//
//            throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), $apiException->getMessage());
//        } catch (\Throwable $exception) {
//            $this->logger->error(
//                \sprintf('Caught a generic exception in %s of %s', __METHOD__, __CLASS__),
//                [
//                    'transaction' => $transaction,
//                    'exception' => $exception,
//                ]
//            );
//
//            throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), $exception->getMessage());
//        }
//    }

    protected function handleRecurringPayment(
        PaymentTransactionStruct $transaction,
        SalesChannelContext           $salesChannelContext
    ): RedirectResponse
    {
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
}
