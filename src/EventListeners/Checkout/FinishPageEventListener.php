<?php

declare(strict_types=1);

namespace HeidelPayment6\EventListeners\Checkout;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment6\Components\Struct\HirePurchase\InstallmentInfo;
use HeidelPayment6\Components\Struct\PageExtension\Checkout\FinishPageExtension;
use HeidelPayment6\Installers\PaymentInstaller;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\InstalmentPlan;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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

        $heidelpayClient = $this->clientFactory->createClient($salesChannelContext->getSalesChannel()->getId());
        $extension       = new FinishPageExtension();

        /** @var OrderTransactionEntity $transaction */
        foreach ($orderTransactions as $transaction) {
            if (!in_array($transaction->getPaymentMethodId(), PaymentInstaller::getPaymentIds(), false)) {
                continue;
            }

            try {
                $payment     = $heidelpayClient->fetchPaymentByOrderId($transaction->getId());
                $paymentType = $payment->getPaymentType();

                if ($paymentType instanceof InstalmentPlan) {
                    $installmentInfo = (new InstallmentInfo())->fromInstalmentPlan($paymentType);
                    $extension->addInstallmentInfo($installmentInfo);
                }
            } catch (HeidelpayApiException $exception) {
                //catch payment not found exception so that shopware can handle its own errors
                $this->logger->error($exception->getMessage(), [
                    'code'          => $exception->getCode(),
                    'clientMessage' => $exception->getClientMessage(),
                    'file'          => $exception->getFile(),
                    'trace'         => $exception->getTrace(),
                ]);
            }
        }

        $event->getPage()->addExtension('heidelpay', $extension);
    }
}
