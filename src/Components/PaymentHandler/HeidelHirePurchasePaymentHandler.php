<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentHandler;

use HeidelPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

class HeidelHirePurchasePaymentHandler extends AbstractHeidelpayHandler
{
    use CanAuthorize;

    /**
     * {@inheritdoc}
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        parent::pay($transaction, $dataBag, $salesChannelContext);

        $birthday = $dataBag->get('heidelpayBirthday');
        $this->heidelpayCustomer->setBirthDate($birthday);

        try {
            $this->heidelpayClient->createOrUpdateCustomer($this->heidelpayCustomer);

            $returnUrl = $this->authorize($transaction->getReturnUrl());

            if ($this->payment) {
                $this->payment->charge();
            } else {
                throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'Payment process interrupted');
            }

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        }
    }
}
