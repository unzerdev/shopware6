<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\Checkout;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\Struct\InstallmentSecured\InstallmentInfo;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\FinishPageExtension;
use UnzerPayment6\Installer\PaymentInstaller;
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

    public function __construct(ClientFactoryInterface $clientFactory, LoggerInterface $logger)
    {
        $this->clientFactory = $clientFactory;
        $this->logger        = $logger;
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
        $orderTransactions   = $page->getOrder()->getTransactions();

        if (!$orderTransactions) {
            return;
        }

        $unzerPaymentIds        = PaymentInstaller::getPaymentIds();
        $unzerOrderTransactions = $orderTransactions->filter(function (OrderTransactionEntity $orderTransaction) use ($unzerPaymentIds) {
            return in_array($orderTransaction->getPaymentMethodId(), $unzerPaymentIds, false);
        });

        if ($unzerOrderTransactions->count() === 0) {
            return;
        }

        try {
            $unzerClient = $this->clientFactory->createClient($salesChannelContext->getSalesChannel()->getId());
        } catch (\RuntimeException $ex) {
            $this->logger->error($ex->getMessage());

            return;
        }

        $extension = new FinishPageExtension();

        /** @var OrderTransactionEntity $transaction */
        foreach ($unzerOrderTransactions as $transaction) {
            $payment = $this->getPaymentByOrderId($unzerClient, $transaction->getId());

            if (!$payment) {
                $payment = $this->getPaymentByOrderId($unzerClient, $transaction->getOrderId());

                if (!$payment) {
                    return;
                }
            }

            $paymentType = $payment->getPaymentType();

            if ($paymentType instanceof InstalmentPlan) {
                $installmentInfo = (new InstallmentInfo())->fromInstalmentPlan($paymentType);
                $extension->addInstallmentInfo($installmentInfo);
            }
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
