<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Administration;

use DateTime;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Document\Renderer\InvoiceRenderer;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;
use UnzerPayment6\Components\BackwardsCompatibility\InvoiceGenerator;
use UnzerPayment6\Components\BasketConverter\BasketConverterInterface;
use UnzerPayment6\Components\CancelService\CancelServiceInterface;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ResourceHydrator\PaymentResourceHydrator\PaymentResourceHydratorInterface;
use UnzerPayment6\Components\ShipService\ShipServiceInterface;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\Charge;

#[Route(defaults: ['_routeScope' => ['api']])]
class UnzerPaymentTransactionController extends AbstractController
{
    public function __construct(
        private readonly ClientFactoryInterface           $clientFactory,
        private readonly EntityRepository                 $orderTransactionRepository,
        private readonly PaymentResourceHydratorInterface $hydrator,
        private readonly CancelServiceInterface           $cancelService,
        private readonly ShipServiceInterface             $shipService,
        private readonly BasketConverterInterface         $basketConverter,
        private readonly LoggerInterface                  $logger
    )
    {
    }

    #[Route(path: '/api/_action/unzer-payment/transaction/{orderTransactionId}/details', name: 'api.action.unzer.transaction.details', methods: ['GET'])]
    public function fetchTransactionDetails(string $orderTransactionId, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null) {
            throw PaymentException::invalidTransaction($orderTransactionId);
        }

        $client = $this->clientFactory->createClient(KeyPairContext::createFromOrderTransaction($transaction));

        try {
            $payment = $client->fetchPaymentByOrderId($orderTransactionId);
            $payment = $client->fetchPayment($payment);

            $data = $this->hydrator->hydrateArray($payment, $transaction, $client);

            if (!empty($data['basket']['totalValueGross'])) {
                $data['basket'] = $this->basketConverter->populateDeprecatedVariables($data['basket']);
            }
        } catch (UnzerApiException|Throwable $exception) {
            $exceptionReturnValues = $this->handleException($exception, sprintf('Error while executing fetching transaction details for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()));
            return new JsonResponse($exceptionReturnValues[0], $exceptionReturnValues[1]);
        }

        return new JsonResponse($data);
    }

    // TODO: evaluate if GET is the correct method here
    #[Route(path: '/api/_action/unzer-payment/transaction/{orderTransactionId}/charge/{amount}', name: 'api.action.unzer.transaction.charge', methods: ['GET'])]
    public function chargeTransaction(string $orderTransactionId, float $amount, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null) {
            throw PaymentException::invalidTransaction($orderTransactionId);
        }

        $client = $this->clientFactory->createClient(KeyPairContext::createFromOrderTransaction($transaction));

        try {
            $charge = new Charge($amount);

            if ($transaction->getPaymentMethodId() === PaymentInstaller::PAYMENT_ID_PAYLATER_INVOICE) {
                $invoiceNumber = $this->getInvoiceNumber($transaction);

                if ($invoiceNumber !== null) {
                    $charge->setInvoiceId($invoiceNumber);
                }
            }

            $client->performChargeOnPayment($orderTransactionId, $charge);
        } catch (UnzerApiException|Throwable $exception) {
            $exceptionReturnValues = $this->handleException($exception, sprintf('Error while executing charge transaction for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()));
            return new JsonResponse($exceptionReturnValues[0], $exceptionReturnValues[1]);
        }

        return new JsonResponse(['status' => true]);
    }

    // TODO: evaluate if GET is the correct method here
    #[Route(path: '/api/_action/unzer-payment/transaction/{orderTransactionId}/refund/{chargeId}/{amount}', name: 'api.action.unzer.transaction.refund', methods: ['GET'])]
    #[Route(path: '/api/_action/unzer-payment/transaction/{orderTransactionId}/refund/{chargeId}/{amount}/{reasonCode}', name: 'api.action.unzer.transaction.refund.reason', methods: ['GET'])]
    public function refundTransaction(string $orderTransactionId, string $chargeId, float $amount, ?string $reasonCode, Context $context): JsonResponse
    {
        try {
            $this->cancelService->cancelChargeById($orderTransactionId, $chargeId, $amount, $reasonCode, $context);
        } catch (UnzerApiException|Throwable $exception) {
            $exceptionReturnValues = $this->handleException($exception, sprintf('Error while executing refund transaction for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()));
            return new JsonResponse($exceptionReturnValues[0], $exceptionReturnValues[1]);
        }

        return new JsonResponse(['status' => true]);
    }

    // TODO: evaluate if GET is the correct method here
    #[Route(path: '/api/_action/unzer-payment/transaction/{orderTransactionId}/cancel/{authorizationId}/{amount}', name: 'api.action.unzer.transaction.cancel', methods: ['GET'])]
    public function cancelTransaction(string $orderTransactionId, string $authorizationId, float $amount, Context $context): JsonResponse
    {
        try {
            $this->cancelService->cancelAuthorizationById($orderTransactionId, $authorizationId, $amount, $context);
        } catch (UnzerApiException|Throwable $exception) {
            $exceptionReturnValues = $this->handleException($exception, sprintf('Error while executing cancel transaction for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()));
            return new JsonResponse($exceptionReturnValues[0], $exceptionReturnValues[1]);
        }

        return new JsonResponse(['status' => true]);
    }

    // TODO: evaluate if GET is the correct method here
    #[Route(path: '/api/_action/unzer-payment/transaction/{orderTransactionId}/ship', name: 'api.action.unzer.transaction.ship', methods: ['GET'])]
    public function shipTransaction(string $orderTransactionId, Context $context): JsonResponse
    {
        try {
            $result = $this->shipService->shipTransaction($orderTransactionId, $context);
        } catch (UnzerApiException|Throwable $exception) {
            $exceptionReturnValues = $this->handleException($exception, sprintf('Error while executing shipping notification for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()));
            return new JsonResponse($exceptionReturnValues[0], $exceptionReturnValues[1]);
        }

        return new JsonResponse($result);
    }

    protected function handleException(Throwable|UnzerApiException $exception, string $logMessage): array
    {
        $this->logger->error($logMessage, [
            'trace' => $exception->getTraceAsString(),
        ]);

        return [
            [
                'status' => false,
                'errors' => [$exception instanceof UnzerApiException ? $exception->getMerchantMessage() : 'generic-error'],
            ],
            Response::HTTP_BAD_REQUEST,
        ];
    }

    protected function getOrderTransaction(string $orderTransactionId, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociations([
            'order',
            'order.billingAddress',
            'order.currency',
            'order.documents',
            'order.documents.documentType',
            'paymentMethod',
        ]);

        return $this->orderTransactionRepository->search($criteria, $context)->first();
    }

    private function getInvoiceNumber(OrderTransactionEntity $transaction): ?string
    {
        if ($transaction->getOrder() === null || $transaction->getOrder()->getDocuments() === null) {
            return null;
        }

        $documents = $transaction->getOrder()->getDocuments()->getElements();
        $invoiceNumber = null;
        $documentDate = null;

        // get latest invoice document
        foreach ($documents as $document) {
            if ($document->getDocumentType() && $document->getDocumentType()->getTechnicalName() === InvoiceRenderer::TYPE) {
                $newDocumentDate = new DateTime($document->getConfig()['documentDate']);

                if ($documentDate === null || $newDocumentDate->getTimestamp() > $documentDate->getTimestamp()) {
                    $documentDate = $newDocumentDate;
                    $invoiceNumber = $document->getConfig()['documentNumber'];
                }
            }
        }

        return $invoiceNumber;
    }
}