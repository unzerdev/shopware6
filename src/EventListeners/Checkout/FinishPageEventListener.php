<?php

declare(strict_types=1);

namespace HeidelPayment6\EventListeners\Checkout;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment6\Components\Document\InvoiceGenerator;
use HeidelPayment6\Components\Struct\PageExtension\Checkout\FinishPageExtension;
use HeidelPayment6\Components\Struct\TransferInformation\TransferInformation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
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
        $paymentMethodId     = $salesChannelContext->getPaymentMethod()->getId();

        //Only invoice payments need to add further information to the finish page!
        if (!in_array($paymentMethodId, InvoiceGenerator::SUPPORTED_PAYMENT_METHODS)) {
            return;
        }

        $page              = $event->getPage();
        $orderTransactions = $page->getOrder()->getTransactions();

        if (!$orderTransactions) {
            return;
        }

        //Get all transaction of this order with any kind of invoice payment
        $transactions = $this->getInvoiceTransactions($orderTransactions);

        if (empty($transactions)) {
            return;
        }

        $heidelpayClient = $this->clientFactory->createClient($salesChannelContext->getSalesChannel()->getId());
        $extension       = new FinishPageExtension();

        /** @var OrderTransactionEntity $transaction */
        foreach ($transactions as $transaction) {
            /** @var Charge $charge */
            $charge = $heidelpayClient->fetchPaymentByOrderId($transaction->getId())->getChargeByIndex(0);

            $extension->addTransferInformation((new TransferInformation())->fromCharge($charge));
        }

        $event->getPage()->addExtension('heidelpay', $extension);
    }

    private function getInvoiceTransactions(OrderTransactionCollection $transactionCollection): OrderTransactionCollection
    {
        return $transactionCollection->filter(
            static function (OrderTransactionEntity $orderTransaction) {
                return in_array($orderTransaction->getPaymentMethodId(), InvoiceGenerator::SUPPORTED_PAYMENT_METHODS);
            }
        );
    }
}
