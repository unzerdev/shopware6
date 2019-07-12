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
            // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
            // As soon as it's shorter, use $transaction->getReturnUrl() instead!
            $returnUrl = $this->getReturnUrl();

            $charge = $this->paymentType->charge(
                $this->heidelpayBasket->getAmountTotal(),
                $this->heidelpayBasket->getCurrencyCode(),
                $returnUrl,
                $this->heidelpayCustomer,
                $transaction->getOrderTransaction()->getId(),
                $this->heidelpayMetadata,
                $this->heidelpayBasket,
                true
            );

            $this->session->set('heidelpayMetadataId', $charge->getPayment()->getMetadata()->getId());

            return new RedirectResponse(empty($charge->getReturnUrl()) ? $returnUrl : $charge->getReturnUrl());
        } catch (HeidelpayApiException $apiException) {
            //TODO: Error-handling
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
        parent::finalize($transaction, $request, $salesChannelContext);

        $payment = $this->heidelpayClient->fetchPaymentByOrderId($transaction->getOrderTransaction()->getId());

        //TODO: Update the order state corresponding to the state of the payment. Use $payment->isPending..isCanceled and so on.
        //Please keep the StateMachine in mind. Do not update it in the database directly!
    }
}
