<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ShipService;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use UnzerPayment6\Components\BackwardsCompatibility\InvoiceGenerator;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Unzer;

class ShipService implements ShipServiceInterface
{
    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var TransactionStateHandlerInterface */
    private $transactionStateHandler;

    /** @var EntityRepository */
    private $orderTransactionRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        TransactionStateHandlerInterface $transactionStateHandler,
        EntityRepository $orderTransactionRepository,
        LoggerInterface $logger
    ) {
        $this->clientFactory              = $clientFactory;
        $this->transactionStateHandler    = $transactionStateHandler;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->logger                     = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function shipTransaction(string $orderTransactionId, Context $context): array
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null || $transaction->getOrder()->getDocuments() === null) {
            throw new InvalidTransactionException($orderTransactionId);
        }

        $order         = $transaction->getOrder();
        $documents     = $transaction->getOrder()->getDocuments()->getElements();
        $invoiceNumber = null;
        $documentDate  = null;

        foreach ($documents as $document) {
            if ($document->getDocumentType() && $document->getDocumentType()->getTechnicalName() === InvoiceGenerator::getInvoiceTechnicalName()) {
                $newDocumentDate = new DateTime($document->getConfig()['documentDate']);

                if ($documentDate === null || $newDocumentDate->getTimestamp() > $documentDate->getTimestamp()) {
                    $documentDate  = $newDocumentDate;
                    $invoiceNumber = $document->getConfig()['documentNumber'];
                }
            }
        }

        if (!$documentDate) {
            $this->logger->error(sprintf('Error while sending shipping notification for order [%s]: No DocumentDate for invoice found', $order->getOrderNumber()));

            return [
                'status'  => false,
                'message' => 'documentdate-missing-error',
            ];
        }

        if (!$invoiceNumber) {
            $this->logger->error(sprintf('Error while sending shipping notification for order [%s]: No invoiceNumber found', $order->getOrderNumber()));

            return [
                'status'  => false,
                'message' => 'invoice-missing-error',
            ];
        }

        $client  = $this->clientFactory->createClient(KeyPairContext::createFromOrderTransaction($transaction));
        $payment = $this->getPayment($orderTransactionId, $documentDate, $client);

        if ($payment === null) {
            $this->logger->error(sprintf('Error while sending shipping notification for order [%s]: Payment could not be fetched', $order->getOrderNumber()));

            return [
                'status'  => false,
                'message' => 'payment-missing-error',
            ];
        }

        $client->ship($payment, $invoiceNumber, $orderTransactionId);

        $this->transactionStateHandler->transformTransactionState($orderTransactionId, $payment, $context);

        return ['status' => true];
    }

    protected function getOrderTransaction(string $orderTransactionId, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociations([
            'order',
            'order.billingAddress',
            'order.documents',
            'order.documents.documentType',
            'paymentMethod',
        ]);

        return $this->orderTransactionRepository->search($criteria, $context)->first();
    }

    protected function getPayment(string $orderTransactionId, DateTimeInterface $documentDate, Unzer $client): ?Payment
    {
        try {
            $payment = $client->fetchPaymentByOrderId($orderTransactionId);
        } catch (UnzerApiException $exception) {
            return null;
        }

        $paymentType = $payment->getPaymentType();

        if ($paymentType !== null && $paymentType instanceof InstallmentSecured) {
            /** @var DateTime $invoiceDueDate */
            $invoiceDueDate = clone $documentDate;
            /** @var DateInterval $dateInterval */
            $dateInterval = DateInterval::createFromDateString(sprintf('%s months', $paymentType->getNumberOfRates()));
            $invoiceDueDate->add($dateInterval);

            $paymentType->setInvoiceDate($documentDate->format('Y-m-d'));
            $paymentType->setInvoiceDueDate($invoiceDueDate->format('Y-m-d'));

            try {
                $payment->setPaymentType($client->updatePaymentType($paymentType));
            } catch (UnzerApiException $exception) {
                return null;
            }
        }

        return $payment;
    }
}
