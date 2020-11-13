<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;

class UnzerHirePurchasePaymentHandler extends AbstractUnzerPaymentHandler
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

        $currentRequest = $this->getCurrentRequestFromStack($transaction->getOrderTransaction()->getId());

        $birthday = $currentRequest->get('unzerPaymentBirthday', '');
        $this->unzerCustomer->setBirthDate($birthday);

        try {
            $this->unzerClient->createOrUpdateCustomer($this->unzerCustomer);

            $returnUrl = $this->authorize($transaction->getReturnUrl());
            $this->payment->charge();

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            $this->logger->error(
                sprintf('Catched an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'dataBag'     => $dataBag,
                    'context'     => $salesChannelContext,
                    'exception'   => $apiException,
                ]
            );

            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('Catched a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'transaction' => $transaction,
                    'dataBag'     => $dataBag,
                    'context'     => $salesChannelContext,
                    'exception'   => $exception,
                ]
            );

            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }
}
