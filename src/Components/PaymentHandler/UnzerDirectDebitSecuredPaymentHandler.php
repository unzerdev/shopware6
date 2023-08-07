<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\CustomFieldsHelper\CustomFieldsHelperInterface;
use UnzerPayment6\Components\PaymentHandler\Exception\UnzerPaymentProcessException;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\HasDeviceVault;
use UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator\CustomerResourceHydratorInterface;
use UnzerPayment6\Components\ResourceHydrator\ResourceHydratorInterface;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;
use UnzerSDK\Exceptions\UnzerApiException;

class UnzerDirectDebitSecuredPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanCharge;
    use HasDeviceVault;

    public function __construct(
        ResourceHydratorInterface $basketHydrator,
        CustomerResourceHydratorInterface $customerHydrator,
        ResourceHydratorInterface $metadataHydrator,
        EntityRepository $transactionRepository,
        ConfigReaderInterface $configReader,
        TransactionStateHandlerInterface $transactionStateHandler,
        ClientFactoryInterface $clientFactory,
        RequestStack $requestStack,
        LoggerInterface $logger,
        CustomFieldsHelperInterface $customFieldsHelper,
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
            $requestStack,
            $logger,
            $customFieldsHelper
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

        $registerDirectDebit = $dataBag->has(UnzerDirectDebitPaymentHandler::REMEMBER_SEPA_MANDATE_KEY);
        $birthday            = $currentRequest->get('unzerPaymentBirthday', '');

        try {
            if (!empty($birthday)
                && (empty($this->unzerCustomer->getBirthDate()) || $birthday !== $this->unzerCustomer->getBirthDate())) {
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

            if ($registerDirectDebit && $salesChannelContext->getCustomer() !== null && $salesChannelContext->getCustomer()->getGuest() === false) {
                $this->saveToDeviceVault(
                    $salesChannelContext->getCustomer(),
                    UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT_SECURED,
                    $salesChannelContext->getContext(),
                    [
                        'birthDate' => $this->unzerCustomer->getBirthDate(),
                    ]
                );
            }

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

    private function isPaymentAllowed(string $transactionId): bool
    {
        $currentRequest = $this->getCurrentRequestFromStack($transactionId);

        $isSepaAccepted = ((string) $currentRequest->get('acceptSepaMandate', 'off')) === 'on';
        $isNewAccount   = ((string) $currentRequest->get('savedDirectDebitDevice', 'new')) === 'new';

        return ($isSepaAccepted && $isNewAccount) || !$isNewAccount;
    }
}
