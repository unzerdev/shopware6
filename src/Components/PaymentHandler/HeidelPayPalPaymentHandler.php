<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentHandler;

use HeidelPayment6\Components\BookingMode;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

class HeidelPayPalPaymentHandler extends AbstractHeidelpayHandler
{
    /** @var Paypal */
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

        $this->paymentType = new Paypal();
        $this->paymentType->setParentResource($this->heidelpayClient);

        try {
            // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
            // As soon as it's shorter, use $transaction->getReturnUrl() instead!
            $returnUrl   = $this->getReturnUrl();
            $bookingMode = $this->pluginConfig->get('bookingModePayPal');

            if ($bookingMode === BookingMode::CHARGE) {
                $paymentResult = $this->paymentType->charge(
                    $this->heidelpayBasket->getAmountTotalGross(),
                    $this->heidelpayBasket->getCurrencyCode(),
                    $returnUrl,
                    $this->heidelpayCustomer,
                    $transaction->getOrderTransaction()->getId(),
                    $this->heidelpayMetadata,
                    $this->heidelpayBasket
                );
            } else {
                $paymentResult = $this->paymentType->authorize(
                    $this->heidelpayBasket->getAmountTotalGross(),
                    $this->heidelpayBasket->getCurrencyCode(),
                    $returnUrl,
                    $this->heidelpayCustomer,
                    $transaction->getOrderTransaction()->getId(),
                    $this->heidelpayMetadata,
                    $this->heidelpayBasket
                );
            }

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
