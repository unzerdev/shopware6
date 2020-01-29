<?php

declare(strict_types=1);

namespace HeidelPayment6\EventListeners\Checkout;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment6\Components\Document\InvoiceGenerator;
use HeidelPayment6\Components\Struct\HirePurchase\InstallmentInfo;
use HeidelPayment6\Components\Struct\PageExtension\Checkout\FinishPageExtension;
use HeidelPayment6\Components\Struct\TransferInformation\TransferInformation;
use HeidelPayment6\Installers\PaymentInstaller;
use heidelpayPHP\Resources\InstalmentPlan;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FinishPageEventListener implements EventSubscriberInterface
{
    /** @var ClientFactoryInterface */
    private $clientFactory;

    public function __construct(ClientFactoryInterface $clientFactory)
    {
        $this->clientFactory = $clientFactory;
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

        $page              = $event->getPage();
        $orderTransactions = $page->getOrder()->getTransactions();

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

            $payment = $heidelpayClient->fetchPaymentByOrderId($transaction->getId());

            if ($payment->getPaymentType() instanceof InstalmentPlan) {
                $installmentInfo = (new InstallmentInfo())->fromInstalmentPlan($payment->getPaymentType());

                $extension->addInstallmentInfo($installmentInfo);

                continue;
            }

            if (in_array($transaction->getPaymentMethodId(), InvoiceGenerator::SUPPORTED_PAYMENT_METHODS, false)) {
                /** @var Charge $charge */
                $charge = $payment->getChargeByIndex(0);
                $extension->addTransferInformation((new TransferInformation())->fromCharge($charge));
            }
        }

        $event->getPage()->addExtension('heidelpay', $extension);
    }
}
