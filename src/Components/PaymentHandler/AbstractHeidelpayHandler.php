<?php

namespace HeidelPayment\Components\PaymentHandler;

use HeidelPayment\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment\Services\Heidelpay\Hydrator\HeidelpayHydratorInterface;
use HeidelPayment\Services\TransactionStateHandlerInterface;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\Metadata;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractHeidelpayHandler implements AsynchronousPaymentHandlerInterface
{
    /** @var BasePaymentType */
    protected $paymentType;

    /** @var Payment */
    protected $payment;

    /** @var Heidelpay */
    protected $heidelpayClient;

    /** @var Customer */
    protected $heidelpayCustomer;

    /** @var Basket */
    protected $heidelpayBasket;

    /** @var Metadata */
    protected $heidelpayMetadata;

    /** @var SystemConfigService */
    protected $configService;

    /** @var SessionInterface */
    protected $session;

    /** @var HeidelpayHydratorInterface */
    private $basketHydrator;

    /** @var HeidelpayHydratorInterface */
    private $customerHydrator;

    /** @var HeidelpayHydratorInterface */
    private $metadataHydrator;

    /** @var TransactionStateHandlerInterface */
    private $transactionStateHandler;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var RouterInterface */
    private $router;

    /** @var string */
    private $resourceId;

    public function __construct(
        HeidelpayHydratorInterface $basketHydrator,
        HeidelpayHydratorInterface $customerHydrator,
        HeidelpayHydratorInterface $metadataHydrator,
        SystemConfigService $configService,
        TransactionStateHandlerInterface $transactionStateHandler,
        ClientFactoryInterface $clientFactory,
        RouterInterface $router, // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
        SessionInterface $session // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
    ) {
        $this->basketHydrator          = $basketHydrator;
        $this->customerHydrator        = $customerHydrator;
        $this->metadataHydrator        = $metadataHydrator;
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
        $client = $this->clientFactory->createClient($salesChannelContext->getSalesChannel()->getId());

        $this->resourceId = $dataBag->get('heidelpayResourceId');

        if (!empty($this->resourceId)) {
            $this->paymentType = $client->fetchPaymentType($this->resourceId);

            $this->heidelpayBasket   = $this->basketHydrator->hydrateObject($salesChannelContext, $transaction);
            $this->heidelpayCustomer = $this->customerHydrator->hydrateObject($salesChannelContext, $transaction);
            $this->heidelpayMetadata = $this->metadataHydrator->hydrateObject($salesChannelContext, $transaction);
        }

        return new RedirectResponse($transaction->getReturnUrl());
    }

    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        $client  = $this->clientFactory->createClient($salesChannelContext->getSalesChannel()->getId());
        $payment = $client->fetchPaymentByOrderId($transaction->getOrderTransaction()->getId());

        $this->transactionStateHandler->transformTransactionState(
            $transaction->getOrderTransaction(),
            $payment,
            $salesChannelContext->getContext()
        );

        $this->session->remove('heidelpayMetadataId');
    }

    /**
     * @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
     *
     * @return string
     */
    protected function getReturnUrl(): string
    {
        return $this->router->generate('heidelpay_finalize_payment', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
