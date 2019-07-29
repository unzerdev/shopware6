<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentHandler;

use HeidelPayment\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment\Components\JsonSerializationTrait;
use HeidelPayment\Components\ResourceHydrator\ResourceHydratorInterface;
use HeidelPayment\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use HeidelPayment\Installers\CustomFieldInstaller;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\Metadata;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractHeidelpayHandler implements AsynchronousPaymentHandlerInterface
{
    /** @var AbstractHeidelpayResource|BasePaymentType */
    protected $paymentType;

    /** @var Payment */
    protected $payment;

    /** @var Heidelpay */
    protected $heidelpayClient;

    /** @var Customer */
    // phpstan-ignore-next-line
    protected $heidelpayCustomer;

    /** @var Basket */
    // phpstan-ignore-next-line
    protected $heidelpayBasket;

    /** @var Metadata */
    // phpstan-ignore-next-line
    protected $heidelpayMetadata;

    /** @var ConfigReaderInterface */
    protected $configService;

    /** @var SessionInterface */
    protected $session;

    /** @var ResourceHydratorInterface */
    private $basketHydrator;

    /** @var ResourceHydratorInterface */
    private $customerHydrator;

    /** @var ResourceHydratorInterface */
    private $metadataHydrator;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var TransactionStateHandlerInterface */
    private $transactionStateHandler;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var RouterInterface */
    private $router;

    /** @var string */
    private $resourceId;

    public function __construct(
        ResourceHydratorInterface $basketHydrator,
        ResourceHydratorInterface $customerHydrator,
        ResourceHydratorInterface $metadataHydrator,
        EntityRepositoryInterface $transactionRepository,
        ConfigReaderInterface $configService,
        TransactionStateHandlerInterface $transactionStateHandler,
        ClientFactoryInterface $clientFactory,
        RouterInterface $router, // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
        SessionInterface $session // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
    ) {
        $this->basketHydrator          = $basketHydrator;
        $this->customerHydrator        = $customerHydrator;
        $this->metadataHydrator        = $metadataHydrator;
        $this->transactionRepository   = $transactionRepository;
        $this->configService           = $configService;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->clientFactory           = $clientFactory;
        $this->router                  = $router;
        $this->session                 = $session;
    }

    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $this->heidelpayClient = $this->clientFactory->createClient($salesChannelContext->getSalesChannel()->getId());

        $this->resourceId = $dataBag->get('heidelpayResourceId');

        $this->heidelpayBasket   = $this->basketHydrator->hydrateObject($salesChannelContext, $transaction);
        $this->heidelpayCustomer = $this->customerHydrator->hydrateObject($salesChannelContext, $transaction);
        $this->heidelpayMetadata = $this->metadataHydrator->hydrateObject($salesChannelContext, $transaction);

        if (!empty($this->resourceId)) {
            $this->paymentType = $this->heidelpayClient->fetchPaymentType($this->resourceId);
        }

        return new RedirectResponse($transaction->getReturnUrl());
    }

    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        $this->heidelpayClient = $this->clientFactory->createClient($salesChannelContext->getSalesChannel()->getId());
        $payment               = $this->heidelpayClient->fetchPaymentByOrderId($transaction->getOrderTransaction()->getId());

        $this->transactionStateHandler->transformTransactionState(
            $transaction->getOrderTransaction(),
            $payment,
            $salesChannelContext->getContext()
        );

        $this->setIsHeidelpayTransaction($transaction, $salesChannelContext);

        $this->session->remove('heidelpayMetadataId');
    }

    /**
     * @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
     *
     * @return string
     */
    protected function getReturnUrl(): string
    {
        return $this->router->generate('heidelpay.payment.finalize', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    protected function setIsHeidelpayTransaction(
        AsyncPaymentTransactionStruct $transaction,
        SalesChannelContext $salesChannelContext
    ): void {
        $customFields = $transaction->getOrderTransaction()->getCustomFields() ?? [];
        $customFields = array_merge($customFields, [CustomFieldInstaller::HEIDELPAY_IS_TRANSACTION => true]);

        $update = [
            'id'           => $transaction->getOrderTransaction()->getId(),
            'customFields' => $customFields,
        ];

        $this->transactionRepository->update([$update], $salesChannelContext->getContext());
    }
}
