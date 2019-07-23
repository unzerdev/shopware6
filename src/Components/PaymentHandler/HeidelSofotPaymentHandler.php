<?php

namespace HeidelPayment\Components\PaymentHandler;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Sofort;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @property Sofort $paymentType
 */
class HeidelSofotPaymentHandler extends AbstractHeidelpayHandler
{
    /**
     * {@inheritdoc}
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        try {
            // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
            // As soon as it's shorter, use $transaction->getReturnUrl() instead!
            $returnUrl = $this->getReturnUrl();

            $result = $this->paymentType->charge(
                $this->heidelpayBasket->getAmountTotal(),
                $this->heidelpayBasket->getCurrencyCode(),
                $returnUrl,
                $this->heidelpayCustomer,
                $this->heidelpayBasket->getOrderId(),
                $this->heidelpayMetadata,
                $this->heidelpayBasket
            );

            $redirectUrl = $result->getPayment()->getRedirectUrl();
        } catch (HeidelpayApiException $apiException) {
            $redirectUrl = $this->getHeidelpayErrorUrl($apiException->getClientMessage());
        }

        return new RedirectResponse($redirectUrl);
    }
}
