<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use UnzerPayment6\Components\PaymentHandler\Exception\UnzerPaymentProcessException;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\HasRiskDataTrait;
use UnzerPayment6\Components\PaymentHandler\Traits\HasTransferInfoTrait;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\EmbeddedResources\CompanyInfo;

class UnzerPaylaterInvoicePaymentHandler extends AbstractUnzerPaymentHandler
{
    use HasTransferInfoTrait;
    use CanAuthorize;
    use CanCharge;
    use HasRiskDataTrait;

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

        try {
            $this->updateUnzerCustomer($currentRequest);

            $riskData = $this->generateRiskDataResource($transaction, $salesChannelContext);

            if (null === $riskData) {
                throw new \RuntimeException('fraud prevention session id is missing from the current request');
            }

            $returnUrl = $this->authorize(
                $transaction->getReturnUrl(),
                $transaction->getOrderTransaction()->getAmount()->getTotalPrice(),
                null,
                $riskData
            );

            $this->saveTransferInfoFromAuthorize($transaction->getOrderTransaction(), $salesChannelContext->getContext());

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

    private function updateUnzerCustomer(Request $request): void
    {
        $birthday       = $request->get('unzerPaymentBirthday', '');
        $companyType    = $request->get('unzerPaymentCompanyType', '');
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
