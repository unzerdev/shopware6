<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\HasTransferInfoTrait;

class UnzerInvoiceSecuredPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanCharge;
    use HasTransferInfoTrait;


    public function pay(
        Request                  $request,
        PaymentTransactionStruct $transaction,
        Context                  $context,
        ?Struct                  $validateStruct
    ): RedirectResponse
    {
        parent::pay($request, $transaction, $context, $validateStruct);
        $birthday = $request->get('unzerPaymentBirthday', '');

        try {
            if (!empty($birthday)
                && (empty($this->unzerCustomer->getBirthDate()) || $birthday !== $this->unzerCustomer->getBirthDate())) {
                $this->unzerCustomer->setBirthDate($birthday);
                $this->unzerCustomer = $this->unzerClient->createOrUpdateCustomer($this->unzerCustomer);
            }

            $returnUrl = $this->charge($transaction->getReturnUrl());
            $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
            $this->saveTransferInfo($orderTransaction, $context);

            return new RedirectResponse($returnUrl);
        } catch (\Throwable $exception) {
            $this->handlePayException($exception, $request, $transaction, $context);
        }
    }
}
