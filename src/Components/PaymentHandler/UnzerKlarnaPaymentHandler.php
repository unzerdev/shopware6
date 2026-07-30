<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerSDK\Resources\PaymentTypes\Klarna;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;

class UnzerKlarnaPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanAuthorize;
    use CanCharge;

    /**
     * {@inheritdoc}
     */
    public function pay(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        ?Struct $validateStruct
    ): RedirectResponse {
        $this->paymentType = new Klarna();
        parent::pay(
            $request,
            $transaction,
            $context,
            $validateStruct
        );

        try {
            $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
            $salesChannelId = $orderTransaction->getOrder()->getSalesChannelId();
            $transactionModifier = function (Authorization|Charge $authorization) use ($salesChannelId): void {
                $termsUrl = $this->router->generate('frontend.cms.page.full', ['id' => $this->configReader->getSingleValue('core.basicInformation.tosPage', $salesChannelId)], UrlGeneratorInterface::ABSOLUTE_URL);
                $privacyUrl = $this->router->generate('frontend.cms.page.full', ['id' => $this->configReader->getSingleValue('core.basicInformation.privacyPage', $salesChannelId)], UrlGeneratorInterface::ABSOLUTE_URL);
                $authorization->setTermsAndConditionUrl($termsUrl);
                $authorization->setPrivacyPolicyUrl($privacyUrl);
            };

            $returnUrl = $this->authorize(
                returnUrl: $transaction->getReturnUrl(),
                amount: $this->unzerBasket->getTotalValueGross(),
                authorizationModifier: $transactionModifier
            );

            return new RedirectResponse($returnUrl);
        } catch (\Throwable $exception) {
            $this->handlePayException(
                $exception,
                $request,
                $transaction,
                $context
            );
        }
    }
}
