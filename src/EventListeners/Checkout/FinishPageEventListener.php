<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\Checkout;

use Psr\Log\LoggerInterface;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\Struct\InstallmentSecured\InstallmentInfo;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\FinishPageExtension;
use UnzerPayment6\Components\TransactionSelectionHelper\TransactionSelectionHelperInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\InstalmentPlan;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Unzer;

class FinishPageEventListener implements EventSubscriberInterface
{
    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var TransactionSelectionHelperInterface */
    private $transactionSelectionHelper;

    public function __construct(ClientFactoryInterface $clientFactory, LoggerInterface $logger, TransactionSelectionHelperInterface $transactionSelectionHelper)
    {
        $this->clientFactory              = $clientFactory;
        $this->logger                     = $logger;
        $this->transactionSelectionHelper = $transactionSelectionHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinish',
        ];
    }

    public function onCheckoutFinish(CheckoutFinishPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $page                = $event->getPage();
        $unzerTransaction    = $this->transactionSelectionHelper->getBestUnzerTransaction($page->getOrder());

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
        $payment   = $this->getPaymentByOrderId($unzerClient, $unzerTransaction->getId());

        if (!$payment) {
            $payment = $this->getPaymentByOrderId($unzerClient, $unzerTransaction->getOrderId());

            if (!$payment) {
                return;
            }
        }

        $paymentType = $payment->getPaymentType();

        if ($paymentType instanceof InstalmentPlan) {
            $installmentInfo = (new InstallmentInfo())->fromInstalmentPlan($paymentType);
            $extension->addInstallmentInfo($installmentInfo);
        }

        $event->getPage()->addExtension(FinishPageExtension::EXTENSION_NAME, $extension);
    }

    private function getPaymentByOrderId(Unzer $unzerClient, string $orderId): ?Payment
    {
        try {
            return $unzerClient->fetchPaymentByOrderId($orderId);
        } catch (UnzerApiException $exception) {
            //catch payment not found exception so that shopware can handle its own errors
            $this->logger->error($exception->getMessage(), [
                'code'          => $exception->getCode(),
                'clientMessage' => $exception->getClientMessage(),
                'file'          => $exception->getFile(),
                'trace'         => $exception->getTraceAsString(),
            ]);
        }

        return null;
    }
}
