<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ShipService;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Shopware\Core\Checkout\Document\DocumentGenerator\InvoiceGenerator;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
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

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    public function __construct(ClientFactoryInterface $clientFactory, TransactionStateHandlerInterface $transactionStateHandler, EntityRepositoryInterface $orderTransactionRepository)
    {
        $this->clientFactory              = $clientFactory;
        $this->transactionStateHandler    = $transactionStateHandler;
        $this->orderTransactionRepository = $orderTransactionRepository;
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

        $documents     = $transaction->getOrder()->getDocuments()->getElements();
        $invoiceNumber = null;
        $documentDate  = null;

        foreach ($documents as $document) {
            if ($document->getDocumentType() && $document->getDocumentType()->getTechnicalName() === InvoiceGenerator::INVOICE) {
                $documentDate  = new DateTimeImmutable($document->getConfig()['documentDate']);
                $invoiceNumber = $document->getConfig()['documentNumber'];
            }
        }

        if (!$invoiceNumber) {
            return
                [
                    'status'  => false,
                    'message' => 'invoice-missing-error',
                ];
        }

        $client  = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());
        $payment = $this->getPayment($orderTransactionId, $documentDate, $client);

        if ($payment === null) {
            return
                [
                    'status'  => false,
                    'message' => 'Payment could not be fetched',
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
            'order.currency',
            'order.documents',
            'order.documents.documentType',
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

        if ($paymentType !== null && $documentDate !== null && $paymentType instanceof InstallmentSecured) {
            $invoiceDueDate = new DateTime($documentDate->format('c'));
            $invoiceDueDate = date_add($invoiceDueDate, date_interval_create_from_date_string(sprintf('%s months', $paymentType->getNumberOfRates())));

            if (!$invoiceDueDate) {
                return null;
            }

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
