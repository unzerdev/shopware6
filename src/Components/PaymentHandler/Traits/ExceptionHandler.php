<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use UnzerPayment6\Components\PaymentHandler\Exception\UnzerPaymentProcessException;
use UnzerSDK\Exceptions\UnzerApiException;

trait ExceptionHandler
{
    protected function handlePayException(Throwable $exception, Request $request, PaymentTransactionStruct $transaction, Context $context)
    {
        if ($exception instanceof UnzerApiException) {
            $this->logger->error(
                \sprintf('Caught an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'request' => $this->getLoggableRequest($request),
                    'transaction' => $transaction,
                    'exception' => $exception,
                ]
            );
            $this->executeFailTransition(
                $transaction->getOrderTransactionId(),
                $context
            );

            $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
            throw new UnzerPaymentProcessException($orderTransaction->getOrderId(), $transaction->getOrderTransactionId(), $exception);
        } else {
            $this->logger->error(
                \sprintf('Caught a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'request' => $this->getLoggableRequest($request),
                    'transaction' => $transaction,
                    'exception' => $exception,
                ]
            );
            throw PaymentException::asyncProcessInterrupted($transaction->getOrderTransactionId(), $exception->getMessage());
        }
    }
}
