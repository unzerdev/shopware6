<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentHandler;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Invoice;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

class HeidelInvoicePaymentHandler extends AbstractHeidelpayHandler
{
    /** @var Invoice */
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

        try {
            // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
            // As soon as it's shorter, use $transaction->getReturnUrl() instead!
            $returnUrl = $this->getReturnUrl();

            $paymentResult = $this->paymentType->charge(
                $this->heidelpayBasket->getAmountTotalGross(),
                $this->heidelpayBasket->getCurrencyCode(),
                $returnUrl,
                $this->heidelpayCustomer,
                $transaction->getOrderTransaction()->getId(),
                $this->heidelpayMetadata,
                $this->heidelpayBasket,
                true
            );

            $this->session->set('heidelpayMetadataId', $paymentResult->getPayment()->getMetadata()->getId());

            if ($paymentResult->getPayment() && !empty($paymentResult->getRedirectUrl())) {
                $returnUrl = $paymentResult->getRedirectUrl();
            }

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        }
    }
}
