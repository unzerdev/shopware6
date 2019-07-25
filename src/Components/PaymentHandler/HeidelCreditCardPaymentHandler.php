<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentHandler;

use HeidelPayment\Components\BookingMode;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Card;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

        $bookingMode = $this->configService->get('HeidelPayment.config.bookingModeCreditCard', $salesChannelContext->getSalesChannel()->getId());

        try {
            // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
            // As soon as it's shorter, use $transaction->getReturnUrl() instead!
            $returnUrl = $this->getReturnUrl();

            if ($bookingMode === BookingMode::CHARGE) {
                $paymentResult = $this->paymentType->charge(
                    $this->heidelpayBasket->getAmountTotal(),
                    $this->heidelpayBasket->getCurrencyCode(),
                    $returnUrl,
                    $this->heidelpayCustomer,
                    $transaction->getOrderTransaction()->getId(),
                    $this->heidelpayMetadata,
                    $this->heidelpayBasket,
                    true
                );
            } else {
                $paymentResult = $this->paymentType->authorize(
                    $this->heidelpayBasket->getAmountTotal(),
                    $this->heidelpayBasket->getCurrencyCode(),
                    $returnUrl,
                    $this->heidelpayCustomer,
                    $transaction->getOrderTransaction()->getId(),
                    $this->heidelpayMetadata,
                    $this->heidelpayBasket,
                    true
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
