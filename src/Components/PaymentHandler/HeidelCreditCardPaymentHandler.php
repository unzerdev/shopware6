<?php

namespace HeidelPayment\Components\PaymentHandler;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Card;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class HeidelCreditCardPaymentHandler extends AbstractHeidelpayHandler
{
    /** @var Card */
    protected $paymentType;

    /**
     * {@inheritdoc}
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        parent::pay($transaction, $dataBag, $salesChannelContext);

        if ($this->paymentType === null) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'Can not process payment without a valid payment resource.');
        }

        try {
            $payment = $this->paymentType->charge(
                $this->heidelpayBasket->getAmountTotal(),
                $this->heidelpayBasket->getCurrencyCode(),
                $transaction->getReturnUrl(),
                $this->heidelpayCustomer,
                $transaction->getOrderTransaction()->getId(),
                $this->heidelpayMetadata,
                $this->heidelpayBasket,
                true
            );

            return new RedirectResponse(empty($payment->getReturnUrl()) ? $transaction->getReturnUrl() : $payment->getReturnUrl());
        } catch (HeidelpayApiException $apiException) {
            dump($apiException);
            die();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        dump('OKAY BABY!!');
        die();
    }
}
