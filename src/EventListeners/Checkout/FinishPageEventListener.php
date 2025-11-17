<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\Checkout;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ExpressCheckout\ExpressCheckoutService;
use UnzerPayment6\Components\Struct\InstallmentSecured\InstallmentInfo;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\FinishPageExtension;
use UnzerPayment6\Components\TransactionSelectionHelper\TransactionSelectionHelperInterface;
use UnzerPayment6\Components\UnzerUtil\UnzerTransactionUtil;
use UnzerSDK\Resources\InstalmentPlan;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Unzer;

class FinishPageEventListener implements EventSubscriberInterface
{
    public function __construct(
        private ClientFactoryInterface $clientFactory,
        private LoggerInterface $logger,
        private TransactionSelectionHelperInterface $transactionSelectionHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinish',
        ];
    }

    public function onCheckoutFinish(CheckoutFinishPageLoadedEvent $event): void
    {
        $this->unsetExpressData($event->getRequest());
        $salesChannelContext = $event->getSalesChannelContext();
        $page = $event->getPage();
        $unzerTransaction = $this->transactionSelectionHelper->getBestUnzerTransaction($page->getOrder());

        if (!$unzerTransaction) {
            return;
        }

        try {
            $unzerClient = $this->clientFactory->createClient(KeyPairContext::createFromSalesChannelContext($salesChannelContext));
        } catch (\RuntimeException $ex) {
            $this->logger->error($ex->getMessage());

            return;
        }

        $extension = new FinishPageExtension();
        $payment = $this->getPaymentByOrderTransaction($unzerClient, $unzerTransaction, $salesChannelContext->getContext());

        if (!$payment) {
            return;
        }

        $paymentType = $payment->getPaymentType();

        if ($paymentType instanceof InstalmentPlan) {
            $installmentInfo = (new InstallmentInfo())->fromInstalmentPlan($paymentType);
            $extension->addInstallmentInfo($installmentInfo);
        }

        $event->getPage()->addExtension(FinishPageExtension::EXTENSION_NAME, $extension);
    }

    private function getPaymentByOrderTransaction(Unzer $unzerClient, OrderTransactionEntity $orderTransaction, Context $context): ?Payment
    {
        try {
            return UnzerTransactionUtil::fetchPaymentFromOrderTransaction($orderTransaction, $unzerClient);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), [
                'code' => $exception->getCode(),
                'clientMessage' => $exception->getClientMessage(),
                'file' => $exception->getFile(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return null;
    }

    private function unsetExpressData(Request $request): void
    {
        try {
            $session = $request->getSession();
            $session->remove(ExpressCheckoutService::SESSION_APPLEPAY_PAYMENT_TYPE_ID);
            $session->remove(ExpressCheckoutService::SESSION_GOOGLE_PAYMENT_TYPE_ID);
            $session->remove(ExpressCheckoutService::SESSION_PAYPAL_PAYMENT_ID);
            $session->remove(ExpressCheckoutService::SESSION_PAYPAL_PAYMENT_TYPE_ID);
            $session->remove(ExpressCheckoutService::SESSION_SELECTED_EXPRESS_METHOD);
        } catch (\Throwable $exception) {
            // not worth handling
        }
    }
}
