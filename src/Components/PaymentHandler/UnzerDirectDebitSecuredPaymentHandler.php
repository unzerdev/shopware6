<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\CustomFieldsHelper\CustomFieldsHelperInterface;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\HasDeviceVault;
use UnzerPayment6\Components\ResourceHydrator\BasketResourceHydrator;
use UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator\CustomerResourceHydratorInterface;
use UnzerPayment6\Components\ResourceHydrator\MetadataResourceHydrator;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerPayment6\Components\UnzerUtil\UnzerTransactionUtil;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;

class UnzerDirectDebitSecuredPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanCharge;
    use HasDeviceVault;

    public function __construct(
        protected readonly BasketResourceHydrator            $basketHydrator,
        protected readonly CustomerResourceHydratorInterface $customerHydrator,
        protected readonly MetadataResourceHydrator          $metadataHydrator,
        protected readonly EntityRepository                  $transactionRepository,
        protected readonly ConfigReaderInterface             $configReader,
        protected readonly TransactionStateHandlerInterface  $transactionStateHandler,
        protected readonly ClientFactoryInterface            $clientFactory,
        protected readonly RequestStack                      $requestStack,
        protected readonly LoggerInterface                   $logger,
        protected readonly CustomFieldsHelperInterface       $customFieldsHelper,
        protected readonly UnzerTransactionUtil              $transactionUtil,
        protected readonly EntityRepository                  $customerRepository,
        protected UnzerPaymentDeviceRepositoryInterface      $deviceRepository
    )
    {
    }

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


        if (!$this->isPaymentAllowed($transaction->getOrderTransactionId())) {
            throw PaymentException::asyncProcessInterrupted($transaction->getOrderTransactionId(), 'SEPA direct debit mandate has not been accepted by the customer.');
        }
        $saveToDeviceVault = $request->get(UnzerDirectDebitPaymentHandler::REMEMBER_SEPA_MANDATE_KEY) !== null;
        $birthday = $request->get('unzerPaymentBirthday', '');

        try {
            if (!empty($birthday)
                && (empty($this->unzerCustomer->getBirthDate()) || $birthday !== $this->unzerCustomer->getBirthDate())) {
                $this->unzerCustomer->setBirthDate($birthday);
            } else {
                $paymentDevice = $this->deviceRepository->getByPaymentTypeId($this->paymentType->getId(), $context);

                if ($paymentDevice && \array_key_exists('birthDate', $paymentDevice->getData())) {
                    $birthDate = $paymentDevice->getData()['birthDate'];

                    if (!empty($birthDate)) {
                        $this->unzerCustomer->setBirthDate($birthDate);
                    }
                }
            }

            $this->unzerCustomer = $this->unzerClient->createOrUpdateCustomer($this->unzerCustomer);
            $returnUrl = $this->charge($transaction->getReturnUrl());

            if ($saveToDeviceVault) {
                $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
                $this->tryToSaveToDeviceVault(
                    $orderTransaction->getOrder()->getOrderCustomer()->getCustomerId(),
                    UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT_SECURED,
                    $context,
                    [
                        'birthDate' => $this->unzerCustomer->getBirthDate(),
                    ]
                );
            }

            return new RedirectResponse($returnUrl);
        } catch (Throwable $exception) {
            $this->handlePayException($exception, $request, $transaction, $context);
        }
    }

    private function isPaymentAllowed(string $transactionId): bool
    {
        $currentRequest = $this->getCurrentRequestFromStack($transactionId);

        $isSepaAccepted = ((string)$currentRequest->get('acceptSepaMandate', 'off')) === 'on';
        $isNewAccount = ((string)$currentRequest->get('savedDirectDebitDevice', 'new')) === 'new';

        return ($isSepaAccepted && $isNewAccount) || !$isNewAccount;
    }
}
