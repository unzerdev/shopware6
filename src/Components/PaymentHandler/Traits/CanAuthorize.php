<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use RuntimeException;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\PaymentHandler\Exception\UnzerPaymentProcessException;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\EmbeddedResources\RiskData;
use UnzerSDK\Resources\TransactionTypes\Authorization;

trait CanAuthorize
{
    public function authorize(
        string $returnUrl,
        ?float $amount = null,
        ?string $recurrenceType = null,
        ?RiskData $riskData = null
    ): string {
        if ($this->unzerClient === null) {
            throw new RuntimeException('UnzerClient can not be null');
        }

        if (!method_exists($this->unzerClient, 'performAuthorization')) {
            throw new RuntimeException('The SDK Version is older then expected');
        }

        if ($this->paymentType === null) {
            throw new RuntimeException('PaymentType can not be null');
        }

        $authorization = new Authorization(
            $amount ?? $this->unzerBasket->getTotalValueGross(),
            $this->unzerBasket->getCurrencyCode(),
            $returnUrl
        );

        $authorization->setOrderId($this->unzerBasket->getOrderId());
        $authorization->setCard3ds(true);

        if ($recurrenceType !== null) {
            $authorization->setRecurrenceType($recurrenceType);
        }

        if ($riskData !== null) {
            $authorization->setRiskData($riskData);
        }

        $paymentResult = $this->unzerClient->performAuthorization(
            $authorization,
            $this->paymentType,
            $this->unzerCustomer,
            $this->unzerMetadata,
            $this->unzerBasket
        );

        $this->payment = $paymentResult->getPayment();

        if ($this->payment !== null && !empty($paymentResult->getRedirectUrl())) {
            return $paymentResult->getRedirectUrl();
        }

        return $returnUrl;
    }

    protected function payWithBookingMode(
        Request                  $request,
        PaymentTransactionStruct $transaction,
        Context                  $context,
        ?Struct                  $validateStruct,
        string $bookingMode
    ): RedirectResponse
    {

        try {
            $returnUrl = $bookingMode === BookingMode::CHARGE
                ? $this->charge($transaction->getReturnUrl())
                : $this->authorize($transaction->getReturnUrl(), $this->unzerBasket->getTotalValueGross());

            return new RedirectResponse($returnUrl);
        } catch (UnzerApiException $apiException) {
            $this->logger->error(
                \sprintf('Caught an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'request' => $request,
                    'transaction' => $transaction,
                    'exception' => $apiException,
                ]
            );

            $this->executeFailTransition(
                $transaction->getOrderTransactionId(),
                $context
            );
            $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
            throw new UnzerPaymentProcessException($orderTransaction->getOrderId(), $transaction->getOrderTransactionId(), $apiException);
        } catch (Throwable $exception) {
            $this->logger->error(
                \sprintf('Caught a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'request' => $request,
                    'transaction' => $transaction,
                    'exception' => $exception,
                ]
            );

            throw PaymentException::asyncProcessInterrupted($transaction->getOrderTransactionId(), $exception->getMessage());
        }
    }
}
