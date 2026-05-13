<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ExpressCheckout\ExpressCheckoutService;
use UnzerPayment6\Components\PaymentHandler\Exception\UnzerPaymentProcessException;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\CanRecur;
use UnzerPayment6\Components\PaymentHandler\Traits\HasDeviceVault;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerPayment6\Installer\CustomFieldInstaller;
use UnzerSDK\Exceptions\UnzerApiException;
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
     * {@inheritdoc}
     */
    public function pay(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        ?Struct $validateStruct
    ): RedirectResponse {
        $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
        if ($request->getSession()->get(ExpressCheckoutService::SESSION_PAYPAL_PAYMENT_ID)) {
            try {
                return $this->payExpress(
                    $request->getSession()->get(ExpressCheckoutService::SESSION_PAYPAL_PAYMENT_ID),
                    $transaction,
                    $orderTransaction,
                    $request,
                    $context
                );
            } catch (\Throwable $e) {
                $request->getSession()->remove(ExpressCheckoutService::SESSION_PAYPAL_PAYMENT_ID);
                // continue with default paypal flow
            }
        }
        parent::pay($request, $transaction, $context, $validateStruct);

        if (!empty($this->paymentType)) {
            // this is from a saved payment device
            try {
                return $this->handleRecurringPayment($request, $transaction, $context);
            } catch (UnzerPaymentProcessException $e) {
                // something went wrong with the API > fall back to the default flow
                $this->paymentType = null;
            }
        }

        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL, BookingMode::CHARGE);

        try {
            $customer = $orderTransaction->getOrder()->getOrderCustomer()->getCustomer();
            $savePaymentDevice = $request->get(self::SAVE_PAYMENT_DEVICE_KEY) && $customer !== null && $customer->getGuest() === false;
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
                        $transaction->getOrderTransactionId(),
                        $context
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
                $transaction->getOrderTransactionId(),
                $context
            );

            return new RedirectResponse($returnUrl);
        } catch (\Throwable $exception) {
            $this->handlePayException($exception, $request, $transaction, $context);
        }
    }

    public function finalize(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context
    ): void {
        $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
        $this->pluginConfig = $this->configReader->read($orderTransaction->getOrder()->getSalesChannelId());

        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL, BookingMode::CHARGE);

        $transactionCustomFields = $orderTransaction->getCustomFields();
        $savePaymentDevice = !empty($transactionCustomFields[self::SAVE_PAYMENT_DEVICE_KEY]);

        $this->unzerClient = $this->clientFactory->createClientFromSalesChannelId($orderTransaction->getOrder()->getSalesChannelId());

        if (!$savePaymentDevice) {
            parent::finalize($request, $transaction, $context);

            return;
        }

        // else we have to do the charge from a freshly saved payment device

        if ($transactionCustomFields === null || !\array_key_exists($this->sessionPaymentTypeKey, $transactionCustomFields)) {
            throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransactionId(), 'missing payment id');
        }

        $this->recur($orderTransaction, $context);

        try {
            /** @phpstan-ignore-next-line */
            $this->paymentType = $this->fetchPaymentByTypeId($transactionCustomFields[$this->sessionPaymentTypeKey]);

            if ($this->paymentType === null) {
                throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransactionId(), 'missing payment type');
            }

            /** Return urls are needed but are not called */
            $bookingMode === BookingMode::CHARGE
                ? $this->charge('https://not.needed')
                : $this->authorize('https://not.needed');

            $customer = $orderTransaction->getOrder()->getOrderCustomer()->getCustomer();
            if ($customer !== null
                && $customer->getGuest() === false
                && $this->paymentType instanceof Paypal
                && $this->paymentType->getEmail() !== null
            ) {
                $this->saveToDeviceVault(
                    $customer,
                    UnzerPaymentDeviceEntity::DEVICE_TYPE_PAYPAL,
                    $context
                );
            }

            $this->transactionStateHandler->transformTransactionState(
                $transaction->getOrderTransactionId(),
                $this->payment,
                $context
            );

            $this->customFieldsHelper->setOrderTransactionCustomFields($orderTransaction, $context);
        } catch (UnzerApiException $apiException) {
            $this->logger->error(
                \sprintf('Caught an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'exception' => $apiException,
                ]
            );

            throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransactionId(), $apiException->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->error(
                \sprintf('Caught a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'exception' => $exception,
                ]
            );

            throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransactionId(), $exception->getMessage());
        }
    }

    protected function handleRecurringPayment(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context
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
                $transaction->getOrderTransactionId(),
                $context
            );

            return new RedirectResponse($returnUrl);
        } catch (\Throwable $exception) {
            $this->handlePayException($exception, $request, $transaction, $context);
        }
    }

    private function payExpress(
        string $unzerPaymentId,
        PaymentTransactionStruct $transaction,
        OrderTransactionEntity $orderTransaction,
        Request $currentRequest,
        Context $context
    ) {
        $unzerClient = $this->unzerClient = $this->clientFactory->createClientFromSalesChannelId($orderTransaction->getOrder()->getSalesChannelId(), $currentRequest);
        $payment = $unzerClient->fetchPayment($unzerPaymentId);
        $unzerBasket = $this->basketHydrator->hydrateObject($orderTransaction);
        $unzerBasket->setId($payment->getBasket()->getId());
        $unzerBasket->setOrderId($orderTransaction->getId());
        $unzerClient->updateBasket($unzerBasket);

        // this does implicitly update the customer object
        $this->getUnzerCustomer($payment->getCustomer()?->getId() ?? '', $orderTransaction->getPaymentMethodId(), $orderTransaction, $context);

        if (empty($payment->getCharges())) {
            $authorization = new Authorization(
                $unzerBasket->getTotalValueGross(),
                $unzerBasket->getCurrencyCode(),
                $transaction->getReturnUrl()
            );
            $authorization->setInvoiceId($orderTransaction->getOrder()->getOrderNumber());
            $authorization->setOrderId($orderTransaction->getId());
            $unzerClient->updateAuthorization($payment->getId(), $authorization);
        } else {
            $charge = new Charge(
                $unzerBasket->getTotalValueGross(),
                $unzerBasket->getCurrencyCode(),
                $transaction->getReturnUrl()
            );
            $charge->setInvoiceId($orderTransaction->getOrder()->getOrderNumber());
            $charge->setOrderId($orderTransaction->getId());
            $unzerClient->updateCharge($payment->getId(), $charge);
        }
        $this->persistPaymentInformation(
            [
                $this->sessionIsRecurring => false,
                $this->sessionPaymentTypeKey => $payment->getPaymentType()->getId(),
                CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY => $payment->getId(),
            ],
            $transaction->getOrderTransactionId(),
            $context
        );

        return new RedirectResponse($transaction->getReturnUrl());
    }
}
