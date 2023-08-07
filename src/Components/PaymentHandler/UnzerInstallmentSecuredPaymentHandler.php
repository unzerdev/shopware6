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
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\UnzerPayment6;
use UnzerSDK\Exceptions\UnzerApiException;

class UnzerInstallmentSecuredPaymentHandler extends AbstractUnzerPaymentHandler
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

        $this->unzerBasket->setTotalValueGross($this->unzerBasket->getTotalValueGross());

        $currentRequest = $this->getCurrentRequestFromStack($transaction->getOrderTransaction()->getId());

        $birthday = $currentRequest->get('unzerPaymentBirthday', '');

        try {
            if (!empty($birthday)
                && (empty($this->unzerCustomer->getBirthDate()) || $birthday !== $this->unzerCustomer->getBirthDate())) {
                $this->unzerCustomer->setBirthDate($birthday);
                $this->unzerClient->createOrUpdateCustomer($this->unzerCustomer);
            }

            /** @var int $currencyPrecision */
            $currencyPrecision = $transaction->getOrder()->getCurrency() !== null ? min(
                $transaction->getOrder()->getCurrency()->getItemRounding()->getDecimals(),
                UnzerPayment6::MAX_DECIMAL_PRECISION
            ) : UnzerPayment6::MAX_DECIMAL_PRECISION;

            $returnUrl = $this->authorize(
                $transaction->getReturnUrl(),
                round($transaction->getOrder()->getAmountTotal(), $currencyPrecision)
            );

            /** @phpstan-ignore-next-line */
            $this->payment->charge(round($transaction->getOrder()->getAmountTotal(), $currencyPrecision));

            return new RedirectResponse($returnUrl);
        } catch (UnzerApiException $apiException) {
            $this->logger->error(
                sprintf('Caught an API exception in %s of %s', __METHOD__, __CLASS__),
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
                sprintf('Caught a generic exception in %s of %s', __METHOD__, __CLASS__),
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
