<?php

namespace HeidelPayment\Components\PaymentHandler;

use HeidelPayment\Services\Heidelpay\Hydrator\HeidelpayHydratorInterface;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\Metadata;
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

    /** @var Heidelpay */
    protected $heidelpayClient;

    /** @var Customer */
    protected $heidelpayCustomer;

    /** @var Basket */
    protected $heidelpayBasket;

    /** @var Metadata */
    protected $heidelpayMetadata;

    /** @var SessionInterface */
    protected $session;

    /** @var HeidelpayHydratorInterface */
    private $basketHydrator;

    /** @var HeidelpayHydratorInterface */
    private $customerHydrator;

    /** @var HeidelpayHydratorInterface */
    private $metadataHydrator;

    /** @var SystemConfigService */
    private $configService;

    /** @var string */
    private $resourceId;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        HeidelpayHydratorInterface $basketHydrator,
        HeidelpayHydratorInterface $customerHydrator,
        HeidelpayHydratorInterface $metadataHydrator,
        SystemConfigService $configService,
        RouterInterface $router, // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
        SessionInterface $session // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
    ) {
        $this->basketHydrator   = $basketHydrator;
        $this->customerHydrator = $customerHydrator;
        $this->metadataHydrator = $metadataHydrator;
        $this->configService    = $configService;
        $this->router           = $router;
        $this->session          = $session;
    }

    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $this->heidelpayClient = $this->getHeidelpayClient($salesChannelContext);
        $this->resourceId      = $dataBag->get('heidelpayResourceId');

        if (!empty($this->resourceId)) {
            $this->paymentType = $this->heidelpayClient->fetchPaymentType($this->resourceId);

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
        $this->heidelpayClient = $this->getHeidelpayClient($salesChannelContext);
    }

    protected function getHeidelpayClient(SalesChannelContext $context): Heidelpay
    {
        $privateKey = $this->configService->get('HeidelPayment.config.privateKey', $context->getSalesChannel()->getId());

        //TODO: Check if we can get the current locale code | Not relevant for this early phase. TBD before 01.08.2019
        $locale = 'en_GB';

        return new Heidelpay($privateKey, $locale);
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
