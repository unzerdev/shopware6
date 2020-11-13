<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Card;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\HasDeviceVault;
use UnzerPayment6\Components\ResourceHydrator\ResourceHydratorInterface;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;

class UnzerCreditCardPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanCharge;
    use CanAuthorize;
    use HasDeviceVault;

    /** @var Card */
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

        if ($this->paymentType === null) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'Can not process payment without a valid payment resource.');
        }

        $bookingMode         = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_CARD, BookingMode::CHARGE);
        $registerCreditCards = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_REGISTER_CARD, false);

        try {
            $returnUrl = $bookingMode === BookingMode::CHARGE
                ? $this->charge($transaction->getReturnUrl())
                : $this->authorize($transaction->getReturnUrl());

            if ($registerCreditCards && $salesChannelContext->getCustomer() !== null) {
                $this->saveToDeviceVault(
                    $salesChannelContext->getCustomer(),
                    UnzerPaymentDeviceEntity::DEVICE_TYPE_CREDIT_CARD,
                    $salesChannelContext->getContext()
                );
            }

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        } catch (Throwable $exception) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }
}
