<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Administration;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Shopware\Core\Checkout\Document\DocumentGenerator\InvoiceGenerator;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use UnzerPayment6\Components\CancelService\CancelServiceInterface;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ResourceHydrator\PaymentResourceHydrator\PaymentResourceHydratorInterface;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Unzer;

/**
 * @RouteScope(scopes={"api"})
 */
class UnzerPaymentTransactionController extends AbstractController
{
    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    /** @var PaymentResourceHydratorInterface */
    private $hydrator;

    /** @var TransactionStateHandlerInterface */
    private $transactionStateHandler;

    /** @var CancelServiceInterface */
    private $cancelService;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        EntityRepositoryInterface $orderTransactionRepository,
        PaymentResourceHydratorInterface $hydrator,
        TransactionStateHandlerInterface $transactionStateHandler,
        CancelServiceInterface $cancelService
    ) {
        $this->clientFactory              = $clientFactory;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->hydrator                   = $hydrator;
        $this->transactionStateHandler    = $transactionStateHandler;
        $this->cancelService              = $cancelService;
    }

    /**
     * @Route("/api/v{version}/_action/unzer-payment/transaction/{orderTransactionId}/details", name="api.action.unzer.transaction.details", methods={"GET"})
     */
    public function fetchTransactionDetails(string $orderTransactionId, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null) {
            throw new InvalidTransactionException($orderTransactionId);
        }

        $client = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());

        try {
            $payment          = $client->fetchPaymentByOrderId($orderTransactionId);
            $payment          = $client->fetchPayment($payment);
            $orderTransaction = $this->getOrderTransaction($orderTransactionId, $context);

            $data = $this->hydrator->hydrateArray($payment, $orderTransaction);
        } catch (UnzerApiException $exception) {
            return new JsonResponse(
                [
                    'status'  => false,
                    'message' => $exception->getMerchantMessage(),
                ],
                Response::HTTP_BAD_REQUEST);
        } catch (Throwable $exception) {
            return new JsonResponse(
                [
                    'status'  => false,
                    'message' => 'generic-error',
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/v{version}/_action/unzer-payment/transaction/{orderTransactionId}/charge/{amount}", name="api.action.unzer.transaction.charge", methods={"GET"})
     */
    public function chargeTransaction(string $orderTransactionId, float $amount, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null) {
            throw new InvalidTransactionException($orderTransactionId);
        }

        $client = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());

        try {
            $client->chargeAuthorization($orderTransactionId, $amount);
        } catch (UnzerApiException $exception) {
            return new JsonResponse(
                [
                    'status'  => false,
                    'message' => $exception->getMerchantMessage(),
                ],
                Response::HTTP_BAD_REQUEST);
        } catch (Throwable $exception) {
            return new JsonResponse(
                [
                    'status'  => false,
                    'message' => 'generic-error',
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(['status' => true]);
    }

    /**
     * @Route("/api/v{version}/_action/unzer-payment/transaction/{orderTransactionId}/refund/{chargeId}/{amount}", name="api.action.unzer.transaction.refund", methods={"GET"})
     */
    public function refundTransaction(string $orderTransactionId, string $chargeId, float $amount, Context $context): JsonResponse
    {
        try {
            $this->cancelService->cancelChargeById($orderTransactionId, $chargeId, $amount, $context);
        } catch (UnzerApiException $exception) {
            return new JsonResponse(
                [
                    'status'  => false,
                    'message' => $exception->getMerchantMessage(),
                ],
                Response::HTTP_BAD_REQUEST);
        } catch (Throwable $exception) {
            return new JsonResponse(
                [
                    'status'  => false,
                    'message' => 'generic-error',
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(['status' => true]);
    }

    /**
     * @Route("/api/v{version}/_action/unzer-payment/transaction/{orderTransactionId}/ship", name="api.action.unzer.transaction.ship", methods={"GET"})
     */
    public function shipTransaction(string $orderTransactionId, Context $context): JsonResponse
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
            return new JsonResponse(
                [
                    'status'  => false,
                    'message' => 'invoice-missing-error',
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $client  = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());
        $payment = $this->getPayment($orderTransactionId, $documentDate, $client);

        if ($payment === null) {
            return new JsonResponse(
                [
                    'status'  => false,
                    'message' => 'Payment could not be fetched',
                ],
                Response::HTTP_BAD_REQUEST);
        }

        try {
            $client->ship($payment, $invoiceNumber, $orderTransactionId);

            $this->transactionStateHandler->transformTransactionState($orderTransactionId, $payment, $context);
        } catch (UnzerApiException $exception) {
            return new JsonResponse(
                [
                    'status'  => false,
                    'message' => $exception->getMerchantMessage(),
                ],
                Response::HTTP_BAD_REQUEST);
        } catch (Throwable $exception) {
            return new JsonResponse(
                [
                    'status'  => false,
                    'message' => 'generic-error',
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(['status' => true]);
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
