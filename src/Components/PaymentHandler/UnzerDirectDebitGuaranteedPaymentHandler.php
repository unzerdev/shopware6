<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\HasDeviceVault;
use UnzerPayment6\Components\ResourceHydrator\ResourceHydratorInterface;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;

class UnzerDirectDebitGuaranteedPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanCharge;
    use HasDeviceVault;

    public function __construct(
        ResourceHydratorInterface $basketHydrator,
        ResourceHydratorInterface $customerHydrator,
        ResourceHydratorInterface $metadataHydrator,
        EntityRepositoryInterface $transactionRepository,
        ConfigReaderInterface $configReader,
        TransactionStateHandlerInterface $transactionStateHandler,
        ClientFactoryInterface $clientFactory,
        RequestStack $requestStack,
        UnzerPaymentDeviceRepositoryInterface $deviceRepository
    ) {
        parent::__construct(
            $basketHydrator,
            $customerHydrator,
            $metadataHydrator,
            $transactionRepository,
            $configReader,
            $transactionStateHandler,
            $clientFactory,
            $requestStack
        );

        $this->deviceRepository = $deviceRepository;
    }

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

        if (!$this->isPaymentAllowed($transaction->getOrderTransaction()->getId())) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'SEPA direct debit mandate has not been accepted by the customer.');
        }

        $registerDirectDebit = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_REGISTER_DIRECT_DEBIT, false);
        $birthday            = $currentRequest->get('unzerPaymentBirthday', '');

        try {
            if (!empty($birthday)) {
                $this->unzerCustomer->setBirthDate($birthday);
            } else {
                $paymentDevice = $this->deviceRepository->getByPaymentTypeId($this->paymentType->getId(), $salesChannelContext->getContext());

                if ($paymentDevice && array_key_exists('birthDate', $paymentDevice->getData())) {
                    $birthDate = $paymentDevice->getData()['birthDate'];

                    if (!empty($birthDate)) {
                        $this->unzerCustomer->setBirthDate($birthDate);
                    }
                }
            }

            $this->unzerCustomer = $this->unzerClient->createOrUpdateCustomer($this->unzerCustomer);

            $returnUrl = $this->charge($transaction->getReturnUrl());

            if ($registerDirectDebit && $salesChannelContext->getCustomer() !== null) {
                $this->saveToDeviceVault(
                    $salesChannelContext->getCustomer(),
                    UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT_GUARANTEED,
                    $salesChannelContext->getContext(),
                    [
                        'birthDate' => $this->unzerCustomer->getBirthDate(),
                    ]
                );
            }

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        } catch (Throwable $exception) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }

    private function isPaymentAllowed(string $transactionId): bool
    {
        $currentRequest = $this->getCurrentRequestFromStack($transactionId);

        $isSepaAccepted = ((string) $currentRequest->get('acceptSepaMandate', 'off')) === 'on';
        $isNewAccount   = ((string) $currentRequest->get('savedDirectDebitDevice', 'new')) === 'new';

        return ($isSepaAccepted && $isNewAccount) || !$isNewAccount;
    }
}
