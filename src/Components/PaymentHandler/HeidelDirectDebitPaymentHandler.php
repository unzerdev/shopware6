<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentHandler;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment6\Components\ConfigReader\ConfigReader;
use HeidelPayment6\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment6\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment6\Components\PaymentHandler\Traits\HasDeviceVault;
use HeidelPayment6\Components\ResourceHydrator\ResourceHydratorInterface;
use HeidelPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use HeidelPayment6\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use HeidelPayment6\DataAbstractionLayer\Repository\PaymentDevice\HeidelpayPaymentDeviceRepositoryInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\SepaDirectDebit;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class HeidelDirectDebitPaymentHandler extends AbstractHeidelpayHandler
{
    use CanCharge;
    use HasDeviceVault;

    /** @var SepaDirectDebit */
    protected $paymentType;

    public function __construct(
        ResourceHydratorInterface $basketHydrator,
        ResourceHydratorInterface $customerHydrator,
        ResourceHydratorInterface $metadataHydrator,
        EntityRepositoryInterface $transactionRepository,
        ConfigReaderInterface $configReader,
        TransactionStateHandlerInterface $transactionStateHandler,
        ClientFactoryInterface $clientFactory,
        RequestStack $requestStack,
        HeidelpayPaymentDeviceRepositoryInterface $deviceRepository
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

        if (!$this->isPaymentAllowed($transaction->getOrderTransaction()->getId())) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'SEPA direct debit mandate has not been accepted by the customer.');
        }

        $registerDirectDebit = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_REGISTER_DIRECT_DEBIT, false);

        try {
            $returnUrl = $this->charge($transaction->getReturnUrl());

            if ($registerDirectDebit && $salesChannelContext->getCustomer() !== null) {
                $this->saveToDeviceVault(
                    $salesChannelContext->getCustomer(),
                    HeidelpayPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT,
                    $salesChannelContext->getContext()
                );
            }

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
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
