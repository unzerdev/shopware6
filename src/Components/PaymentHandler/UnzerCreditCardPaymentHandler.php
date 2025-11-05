<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\HasDeviceVault;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerSDK\Constants\RecurrenceTypes;

class UnzerCreditCardPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanAuthorize;
    use CanCharge;
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
        parent::pay($request, $transaction, $context, $validateStruct);

        if ($this->paymentType === null) {
            throw PaymentException::asyncProcessInterrupted($transaction->getOrderTransactionId(), 'Can not process payment without a valid payment resource.');
        }

        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_CARD, BookingMode::CHARGE);
        $saveToDeviceVault = $request->get(self::SAVE_PAYMENT_DEVICE_KEY) !== null;

        try {
            $recurrenceType = ($this->deviceRepository->exists($this->paymentType->getId(), $context) || $saveToDeviceVault)
                ? RecurrenceTypes::ONE_CLICK
                : null;

            $returnUrl = $bookingMode === BookingMode::CHARGE
                ? $this->charge($transaction->getReturnUrl(), $recurrenceType)
                : $this->authorize($transaction->getReturnUrl(), $this->unzerBasket->getTotalValueGross(), $recurrenceType);

            if ($saveToDeviceVault) {
                $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
                $this->tryToSaveToDeviceVault(
                    $orderTransaction->getOrder()->getOrderCustomer()->getCustomerId(),
                    UnzerPaymentDeviceEntity::DEVICE_TYPE_CREDIT_CARD,
                    $context
                );
            }

            return new RedirectResponse($returnUrl);
        } catch (\Throwable $exception) {
            $this->handlePayException($exception, $request, $transaction, $context);
        }
    }
}
