<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ExpressCheckout\ExpressCheckoutService;
use UnzerPayment6\Components\PaymentHandler\Exception\UnzerPaymentProcessException;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Unzer;

class UnzerGooglePayPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanAuthorize;
    use CanCharge;

    /**
     * @var BasePaymentType
     */
    protected $paymentType;

    /**
     * {@inheritdoc}
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!empty($currentRequest->getSession()->get(ExpressCheckoutService::SESSION_GOOGLE_PAYMENT_TYPE_ID))) {
            $client = $this->clientFactory->createClientFromSalesChannelId($salesChannelContext->getSalesChannelId(), $currentRequest);
            $this->paymentType = $client->fetchPaymentType($currentRequest->getSession()->get(ExpressCheckoutService::SESSION_GOOGLE_PAYMENT_TYPE_ID));
            $this->isExpress = true;
        }
        parent::pay($transaction, $dataBag, $salesChannelContext);

        if ($this->paymentType === null) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'Can not process payment without a valid payment resource.');
        }

        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_GOOGLE_PAY_BOOKING_MODE, BookingMode::CHARGE);

        try {
            $returnUrl = $bookingMode === BookingMode::CHARGE
                ? $this->charge($transaction->getReturnUrl())
                : $this->authorize($transaction->getReturnUrl(), $this->unzerBasket->getTotalValueGross());

            return new RedirectResponse($returnUrl);
        } catch (UnzerApiException $apiException) {
            $this->logger->error(
                \sprintf('Caught an API exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'dataBag' => $dataBag,
                    'transaction' => $transaction,
                    'exception' => $apiException,
                ]
            );

            $this->executeFailTransition(
                $transaction->getOrderTransaction()->getId(),
                $salesChannelContext->getContext()
            );

            throw new UnzerPaymentProcessException($transaction->getOrder()->getId(), $transaction->getOrderTransaction()->getId(), $apiException);
        } catch (\Throwable $exception) {
            $this->logger->error(
                \sprintf('Caught a generic exception in %s of %s', __METHOD__, __CLASS__),
                [
                    'dataBag' => $dataBag,
                    'transaction' => $transaction,
                    'exception' => $exception,
                ]
            );
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }

    public static function fetchChannelId(Unzer $client): string
    {
        try {
            $keyPair = $client->fetchKeyPair(true);
            foreach ($keyPair->getPaymentTypes() as $paymentType) {
                if ($paymentType->type === 'googlepay') {
                    $channelId = $paymentType->supports[0]->channel ?? null;
                    if ($channelId) {
                        return $channelId;
                    }
                }
            }
        } catch (\Exception $e) {
            // silent to return '' at the end
        }

        // will only be reached, if no channel id was found
        return '';
    }
}
