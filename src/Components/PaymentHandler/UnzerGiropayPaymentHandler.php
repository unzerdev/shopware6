<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Giropay;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;

class UnzerGiropayPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanCharge;

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
            $this->paymentType = $this->unzerClient->createPaymentType(new Giropay());

            $returnUrl = $this->charge($transaction->getReturnUrl());

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        } catch (Throwable $exception) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }
}
