<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentHandler;

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment\Components\ResourceHydrator\ResourceHydratorInterface;
use HeidelPayment\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use HeidelPayment\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use HeidelPayment\DataAbstractionLayer\Repository\PaymentDevice\HeidelpayPaymentDeviceRepositoryInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Card;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

class HeidelCreditCardPaymentHandler extends AbstractHeidelpayHandler
{
    /** @var Card */
    protected $paymentType;

    /** @var HeidelpayPaymentDeviceRepositoryInterface */
    private $deviceRepository;

    public function __construct(
        ResourceHydratorInterface $basketHydrator,
        ResourceHydratorInterface $customerHydrator,
        ResourceHydratorInterface $metadataHydrator,
        EntityRepositoryInterface $transactionRepository,
        ConfigReaderInterface $configService,
        TransactionStateHandlerInterface $transactionStateHandler,
        ClientFactoryInterface $clientFactory,
        RouterInterface $router, // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
        SessionInterface $session, // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
        HeidelpayPaymentDeviceRepositoryInterface $deviceRepository
    ) {
        parent::__construct(
            $basketHydrator,
            $customerHydrator,
            $metadataHydrator,
            $transactionRepository,
            $configService,
            $transactionStateHandler,
            $clientFactory,
            $router,
            $session
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

        $bookingMode         = $this->pluginConfig->get('bookingModeCreditCard', BookingMode::CHARGE);
        $registerCreditCards = $this->pluginConfig->get('registerCreditCard');

        try {
            // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
            // As soon as it's shorter, use $transaction->getReturnUrl() instead!
            $returnUrl = $this->getReturnUrl();

            if ($bookingMode === BookingMode::CHARGE) {
                $paymentResult = $this->paymentType->charge(
                    $this->heidelpayBasket->getAmountTotal(),
                    $this->heidelpayBasket->getCurrencyCode(),
                    $returnUrl,
                    $this->heidelpayCustomer,
                    $transaction->getOrderTransaction()->getId(),
                    $this->heidelpayMetadata,
                    $this->heidelpayBasket,
                    true
                );
            } else {
                $paymentResult = $this->paymentType->authorize(
                    $this->heidelpayBasket->getAmountTotal(),
                    $this->heidelpayBasket->getCurrencyCode(),
                    $returnUrl,
                    $this->heidelpayCustomer,
                    $transaction->getOrderTransaction()->getId(),
                    $this->heidelpayMetadata,
                    $this->heidelpayBasket,
                    true
                );
            }

            if ($registerCreditCards && $salesChannelContext->getCustomer() !== null) {
                $this->saveCreditCard($salesChannelContext->getCustomer(), $salesChannelContext->getContext());
            }

            $this->session->set('heidelpayMetadataId', $paymentResult->getPayment()->getMetadata()->getId());

            if ($paymentResult->getPayment() && !empty($paymentResult->getRedirectUrl())) {
                $returnUrl = $paymentResult->getRedirectUrl();
            }

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        }
    }

    private function saveCreditCard(CustomerEntity $customer, Context $context): void
    {
        if ($this->deviceRepository->exists($this->paymentType->getId(), $context)) {
            return;
        }

        $this->deviceRepository->create(
            $customer,
            HeidelpayPaymentDeviceEntity::DEVICE_TYPE_CREDIT_CARD,
            $this->paymentType->getId(),
            $this->paymentType->expose(),
            $context
        );
    }
}
