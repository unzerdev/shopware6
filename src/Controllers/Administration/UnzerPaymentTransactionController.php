<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Administration;

use DateTime;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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

/**
 * @RouteScope(scopes={"api"})
 * @Route(defaults={"_routeScope": {"api"}})
 */
class UnzerPaymentTransactionController extends AbstractController
{
    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var EntityRepository */
    private $orderTransactionRepository;

    /** @var PaymentResourceHydratorInterface */
    private $hydrator;

    /** @var CancelServiceInterface */
    private $cancelService;

    /** @var ShipServiceInterface */
    private $shipService;

    /** @var BasketConverterInterface */
    private $basketConverter;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        EntityRepository $orderTransactionRepository,
        PaymentResourceHydratorInterface $hydrator,
        CancelServiceInterface $cancelService,
        ShipServiceInterface $shipService,
        BasketConverterInterface $basketConverter,
        LoggerInterface $logger
    ) {
        $this->clientFactory              = $clientFactory;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->hydrator                   = $hydrator;
        $this->cancelService              = $cancelService;
        $this->shipService                = $shipService;
        $this->basketConverter            = $basketConverter;
        $this->logger                     = $logger;
    }

    /**
     * @Route("/api/_action/unzer-payment/transaction/{orderTransactionId}/details", name="api.action.unzer.transaction.details", methods={"GET"})
     * @Route("/api/v{version}/_action/unzer-payment/transaction/{orderTransactionId}/details", name="api.action.unzer.transaction.details.version", methods={"GET"})
     */
    public function fetchTransactionDetails(string $orderTransactionId, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null) {
            throw new InvalidTransactionException($orderTransactionId);
        }

        $client = $this->clientFactory->createClient(KeyPairContext::createFromOrderTransaction($transaction));

        try {
            $payment = $client->fetchPaymentByOrderId($orderTransactionId);
            $payment = $client->fetchPayment($payment);

            $data = $this->hydrator->hydrateArray($payment, $transaction, $client);

            /* Basket V2 since Version 1.1.5 */
            if (!empty($data['basket']['totalValueGross'])) {
                $data['basket'] = $this->basketConverter->populateDeprecatedVariables($data['basket']);
            }
        } catch (UnzerApiException $exception) {
            $this->logger->error(sprintf('Error while executing fetching transaction details for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return new JsonResponse(
                [
                    'status' => false,
                    'errors' => [$exception->getMerchantMessage()],
                ],
                Response::HTTP_BAD_REQUEST);
        } catch (Throwable $exception) {
            $this->logger->error(sprintf('Error while executing fetching transaction details for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return new JsonResponse(
                [
                    'status' => false,
                    'errors' => ['generic-error'],
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/_action/unzer-payment/transaction/{orderTransactionId}/charge/{amount}", name="api.action.unzer.transaction.charge", methods={"GET"})
     * @Route("/api/v{version}/_action/unzer-payment/transaction/{orderTransactionId}/charge/{amount}", name="api.action.unzer.transaction.charge.version", methods={"GET"})
     */
    public function chargeTransaction(string $orderTransactionId, float $amount, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null) {
            throw new InvalidTransactionException($orderTransactionId);
        }

        $client = $this->clientFactory->createClient(KeyPairContext::createFromOrderTransaction($transaction));

        try {
            $charge = new Charge($amount);

            if ($transaction->getPaymentMethodId() === PaymentInstaller::PAYMENT_ID_PAYLATER_INVOICE) {
                $invoiceNumber = $this->getInvoiceNumber($transaction);

                if ($invoiceNumber === null) {
                    return new JsonResponse(
                            [
                            'status' => false,
                            'errors' => ['paylater-invoice-document-required'],
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }

                $charge->setInvoiceId($invoiceNumber);
            }

            $client->performChargeOnPayment($orderTransactionId, $charge);
        } catch (UnzerApiException $exception) {
            $this->logger->error(sprintf('Error while executing charge transaction for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return new JsonResponse(
                [
                    'status' => false,
                    'errors' => [$exception->getMerchantMessage()],
                ],
                Response::HTTP_BAD_REQUEST);
        } catch (Throwable $exception) {
            $this->logger->error(sprintf('Error while executing charge transaction for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return new JsonResponse(
                [
                    'status' => false,
                    'errors' => ['generic-error'],
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(['status' => true]);
    }

    /**
     * @Route("/api/_action/unzer-payment/transaction/{orderTransactionId}/refund/{chargeId}/{amount}", name="api.action.unzer.transaction.refund", methods={"GET"})
     * @Route("/api/_action/unzer-payment/transaction/{orderTransactionId}/refund/{chargeId}/{amount}/{reasonCode}", name="api.action.unzer.transaction.refund.reason", methods={"GET"})
     * @Route("/api/v{version}/_action/unzer-payment/transaction/{orderTransactionId}/refund/{chargeId}/{amount}", name="api.action.unzer.transaction.refund.version", methods={"GET"})
     * @Route("/api/v{version}/_action/unzer-payment/transaction/{orderTransactionId}/refund/{chargeId}/{amount}/{reasonCode}", name="api.action.unzer.transaction.refund.version.reason", methods={"GET"})
     */
    public function refundTransaction(string $orderTransactionId, string $chargeId, float $amount, ?string $reasonCode, Context $context): JsonResponse
    {
        try {
            $this->cancelService->cancelChargeById($orderTransactionId, $chargeId, $amount, $reasonCode, $context);
        } catch (UnzerApiException $exception) {
            $this->logger->error(sprintf('Error while executing refund transaction for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return new JsonResponse(
                [
                    'status' => false,
                    'errors' => [$exception->getMerchantMessage()],
                ],
                Response::HTTP_BAD_REQUEST);
        } catch (Throwable $exception) {
            $this->logger->error(sprintf('Error while executing refund transaction for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return new JsonResponse(
                [
                    'status' => false,
                    'errors' => ['generic-error'],
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(['status' => true]);
    }

    /**
     * @Route("/api/_action/unzer-payment/transaction/{orderTransactionId}/cancel/{authorizationId}/{amount}", name="api.action.unzer.transaction.cancel", methods={"GET"})
     * @Route("/api/v{version}/_action/unzer-payment/transaction/{orderTransactionId}/cancel/{authorizationId}/{amount}", name="api.action.unzer.transaction.cancel.version", methods={"GET"})
     */
    public function cancelTransaction(string $orderTransactionId, string $authorizationId, float $amount, Context $context): JsonResponse
    {
        try {
            $this->cancelService->cancelAuthorizationById($orderTransactionId, $authorizationId, $amount, $context);
        } catch (UnzerApiException $exception) {
            $this->logger->error(sprintf('Error while executing cancel transaction for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return new JsonResponse(
                [
                    'status' => false,
                    'errors' => [$exception->getMerchantMessage()],
                ],
                Response::HTTP_BAD_REQUEST);
        } catch (Throwable $exception) {
            $this->logger->error(sprintf('Error while executing cancel transaction for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return new JsonResponse(
                [
                    'status' => false,
                    'errors' => ['generic-error'],
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(['status' => true]);
    }

    /**
     * @Route("/api/_action/unzer-payment/transaction/{orderTransactionId}/ship", name="api.action.unzer.transaction.ship", methods={"GET"})
     * @Route("/api/v{version}/_action/unzer-payment/transaction/{orderTransactionId}/ship", name="api.action.unzer.transaction.ship.version", methods={"GET"})
     */
    public function shipTransaction(string $orderTransactionId, Context $context): JsonResponse
    {
        try {
            $result = $this->shipService->shipTransaction($orderTransactionId, $context);
        } catch (UnzerApiException $exception) {
            $this->logger->error(sprintf('Error while executing shipping notification for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()), [
                'trace' => $exception->getTraceAsString(),
            ]);
            $result = [
                'status' => false,
                'errors' => [$exception->getMerchantMessage()],
            ];
        } catch (Throwable $exception) {
            $this->logger->error(sprintf('Error while executing shipping notification for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()), [
                'trace' => $exception->getTraceAsString(),
            ]);
            $result = [
                    'status' => false,
                    'errors' => ['generic-error'],
                ];
        }

        return new JsonResponse($result, $result['status'] === false ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK);
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

        $documents     = $transaction->getOrder()->getDocuments()->getElements();
        $invoiceNumber = null;
        $documentDate  = null;

        // get latest invoice document
        foreach ($documents as $document) {
            if ($document->getDocumentType() && $document->getDocumentType()->getTechnicalName() === InvoiceGenerator::getInvoiceTechnicalName()) {
                $newDocumentDate = new DateTime($document->getConfig()['documentDate']);

                if ($documentDate === null || $newDocumentDate->getTimestamp() > $documentDate->getTimestamp()) {
                    $documentDate  = $newDocumentDate;
                    $invoiceNumber = $document->getConfig()['documentNumber'];
                }
            }
        }

        return $invoiceNumber;
    }
}
