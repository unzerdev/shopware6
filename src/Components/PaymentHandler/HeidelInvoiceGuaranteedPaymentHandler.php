<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentHandler;

use HeidelPayment\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment\Components\ResourceHydrator\ResourceHydratorInterface;
use HeidelPayment\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\InvoiceGuaranteed;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

class HeidelInvoiceGuaranteedPaymentHandler extends AbstractHeidelpayHandler
{
    /** @var InvoiceGuaranteed */
    protected $paymentType;

    /** @var ResourceHydratorInterface */
    private $businessCustomerHydrator;

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
        ResourceHydratorInterface $businessCustomerHydrator
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

        $this->businessCustomerHydrator = $businessCustomerHydrator;
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

        try {
            // @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
            // As soon as it's shorter, use $transaction->getReturnUrl() instead!
            $returnUrl         = $this->getReturnUrl();
            $heidelpayCustomer = $this->heidelpayCustomer;

            if ($salesChannelContext->getCustomer()->getCompany() !== null || $salesChannelContext->getCustomer()->getActiveBillingAddress()->getCompany() !== null) {
                $heidelpayCustomer = $this->businessCustomerHydrator->hydrateObject($salesChannelContext, $transaction);
            }

            $paymentResult = $this->paymentType->charge(
                $this->heidelpayBasket->getAmountTotalGross(),
                $this->heidelpayBasket->getCurrencyCode(),
                $returnUrl,
                $heidelpayCustomer,
                $transaction->getOrderTransaction()->getId(),
                $this->heidelpayMetadata,
                $this->heidelpayBasket
            );

            $this->session->set('heidelpayMetadataId', $paymentResult->getPayment()->getMetadata()->getId());

            if ($paymentResult->getPayment() && !empty($paymentResult->getRedirectUrl())) {
                $returnUrl = $paymentResult->getRedirectUrl();
            }

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        }
    }
}
