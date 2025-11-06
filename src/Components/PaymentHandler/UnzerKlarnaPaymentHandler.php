<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Klarna;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;

class UnzerKlarnaPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanAuthorize;
    use CanCharge;

    protected BasePaymentType $paymentType;

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
            $transactionModifier = function (Authorization|Charge $authorization): void {
                $authorization->setTermsAndConditionUrl('https://unzer.com');
                $authorization->setPrivacyPolicyUrl('https://unzer.com');
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
