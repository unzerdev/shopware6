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

    public function __construct(
        HeidelpayHydratorInterface $basketHydrator,
        HeidelpayHydratorInterface $customerHydrator,
        HeidelpayHydratorInterface $metadataHydrator,
        SystemConfigService $configService
    ) {
        $this->basketHydrator   = $basketHydrator;
        $this->customerHydrator = $customerHydrator;
        $this->metadataHydrator = $metadataHydrator;
        $this->configService    = $configService;
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

    protected function getHeidelpayClient(SalesChannelContext $context): Heidelpay
    {
        $privateKey = $this->configService->get('HeidelPayment.config.privateKey', $context->getSalesChannel()->getId());

        //TODO: Check if we can get the current locale code
        $locale = 'en_GB';

        return new Heidelpay($privateKey, $locale);
    }
}
