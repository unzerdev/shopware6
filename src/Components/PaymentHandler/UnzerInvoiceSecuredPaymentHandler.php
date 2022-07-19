<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;
use UnzerPayment6\Components\PaymentHandler\Exception\UnzerPaymentProcessException;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\HasTransferInfoTrait;
use UnzerSDK\Exceptions\UnzerApiException;

class UnzerInvoiceSecuredPaymentHandler extends AbstractUnzerPaymentHandler
{
    use HasTransferInfoTrait;
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
        $currentRequest = $this->getCurrentRequestFromStack($transaction->getOrderTransaction()->getId());
        $birthday       = $currentRequest->get('unzerPaymentBirthday', '');

        try {
            if (!empty($birthday)
                && (empty($this->unzerCustomer->getBirthDate()) || $birthday !== $this->unzerCustomer->getBirthDate())) {
                $this->unzerCustomer->setBirthDate($birthday);
                $this->unzerCustomer = $this->unzerClient->createOrUpdateCustomer($this->unzerCustomer);
            }

            $returnUrl        = $this->charge($transaction->getReturnUrl());
            $orderTransaction = $transaction->getOrderTransaction();
            $this->saveTransferInfo($orderTransaction, $salesChannelContext->getContext());

            return new RedirectResponse($returnUrl);
        } catch (UnzerApiException $apiException) {
            $this->logger->error(
                sprintf('Catched an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'request'     => $this->getLoggableRequest($currentRequest),
                    'transaction' => $transaction,
                    'exception'   => $apiException,
                ]
            );

            $this->executeFailTransition(
                $transaction->getOrderTransaction()->getId(),
                $salesChannelContext->getContext()
            );

            throw new UnzerPaymentProcessException($transaction->getOrder()->getId(), $transaction->getOrderTransaction()->getId(), $apiException);
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('Catched a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'request'     => $this->getLoggableRequest($currentRequest),
                    'transaction' => $transaction,
                    'exception'   => $exception,
                ]
            );

            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }
}
