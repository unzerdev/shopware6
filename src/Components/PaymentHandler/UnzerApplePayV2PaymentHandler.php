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
use UnzerPayment6\Components\ExpressCheckout\ExpressCheckoutService;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;

class UnzerApplePayV2PaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanAuthorize;
    use CanCharge;

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
        try {
            if (!empty($request->getSession()->get(ExpressCheckoutService::SESSION_APPLEPAY_PAYMENT_TYPE_ID))) {
                $client = $this->clientFactory->createClientFromSalesChannelId($orderTransaction->getOrder()->getSalesChannelId(), $request);
                $this->paymentType = $client->fetchPaymentType($request->getSession()->get(ExpressCheckoutService::SESSION_APPLEPAY_PAYMENT_TYPE_ID));
                $this->isExpress = true;
            }
        } catch (\Throwable) {
            $this->isExpress = false;
        }

        parent::pay($request, $transaction, $context, $validateStruct);

        if (empty($this->paymentType)) {
            throw PaymentException::asyncProcessInterrupted($transaction->getOrderTransactionId(), 'Can not process payment without a valid payment resource.');
        }

        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_APPLE_PAY, BookingMode::CHARGE);

        try {
            $returnUrl = $bookingMode === BookingMode::CHARGE
                ? $this->charge($transaction->getReturnUrl())
                : $this->authorize($transaction->getReturnUrl(), $this->unzerBasket->getTotalValueGross());

            return new RedirectResponse($returnUrl);
        } catch (\Throwable $exception) {
            $this->handlePayException($exception, $request, $transaction, $context);
        }
    }
}
