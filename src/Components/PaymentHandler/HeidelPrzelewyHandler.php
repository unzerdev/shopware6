<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentHandler;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Przelewy24;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

class HeidelPrzelewyHandler extends AbstractHeidelpayHandler
{
    /**
     * {@inheritdoc}
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        parent::pay($transaction, $dataBag, $salesChannelContext);

        $this->paymentType = new Przelewy24();
        $this->paymentType->setParentResource($this->heidelpayClient);

        try {
            // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
            // As soon as it's shorter, use $transaction->getReturnUrl() instead!
            $returnUrl = $this->getReturnUrl();

            $result = $this->paymentType->charge(
                $this->heidelpayBasket->getAmountTotalGross(),
                $this->heidelpayBasket->getCurrencyCode(),
                $returnUrl,
                $this->heidelpayCustomer,
                $this->heidelpayBasket->getOrderId(),
                $this->heidelpayMetadata,
                $this->heidelpayBasket
            );

            $this->session->set('heidelpayMetadataId', $result->getPayment()->getMetadata()->getId());

            if ($result->getPayment() && !empty($result->getRedirectUrl())) {
                $returnUrl = $result->getRedirectUrl();
            }

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        }
    }
}
