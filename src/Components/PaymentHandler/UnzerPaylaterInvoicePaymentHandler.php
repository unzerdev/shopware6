<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use RuntimeException;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\HasRiskDataTrait;
use UnzerPayment6\Components\PaymentHandler\Traits\HasTransferInfoTrait;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\EmbeddedResources\CompanyInfo;

class UnzerPaylaterInvoicePaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanAuthorize;
    use CanCharge;
    use HasRiskDataTrait;
    use HasTransferInfoTrait;

    /**
     * {@inheritdoc}
     */
    public function pay(
        Request                  $request,
        PaymentTransactionStruct $transaction,
        Context                  $context,
        ?Struct                  $validateStruct
    ): RedirectResponse
    {
        parent::pay($request, $transaction, $context, $validateStruct);

        try {
            $this->updateUnzerCustomer($request);
            $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
            $riskData = $this->generateRiskDataResource($orderTransaction, $context);

            if ($riskData === null) {
                throw new RuntimeException('fraud prevention session id is missing from the current request');
            }

            $returnUrl = $this->authorize(
                $transaction->getReturnUrl(),
                null,
                null,
                $riskData
            );

            $this->saveTransferInfoFromAuthorize($orderTransaction, $context);

            return new RedirectResponse($returnUrl);
        } catch (Throwable $exception) {
            $this->handlePayException($exception, $request, $transaction, $context);
        }
    }

    /**
     * @throws UnzerApiException
     */
    private function updateUnzerCustomer(Request $request): void
    {
        $birthday = $request->get('unzerPaymentBirthday', '');
        $companyType = $request->get('unzerPaymentCompanyType', '');
        $createOrUpdate = false;

        if (!empty($birthday)
            && (empty($this->unzerCustomer->getBirthDate()) || $birthday !== $this->unzerCustomer->getBirthDate())) {
            $createOrUpdate = true;
            $this->unzerCustomer->setBirthDate($birthday);
        }

        $companyInfo = $this->unzerCustomer->getCompanyInfo() ?? new CompanyInfo();

        if (!empty($companyType) && $companyInfo->getCompanyType() !== $companyType) {
            $createOrUpdate = true;
            $companyInfo->setCompanyType($companyType);
            $this->unzerCustomer->setCompanyInfo($companyInfo);
        }

        if (!$createOrUpdate) {
            return;
        }

        $this->unzerCustomer = $this->unzerClient->createOrUpdateCustomer($this->unzerCustomer);
    }
}
