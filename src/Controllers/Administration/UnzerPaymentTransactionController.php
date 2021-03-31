<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Administration;

use Psr\Log\LoggerInterface;
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
use UnzerPayment6\Components\ShipService\ShipServiceInterface;
use UnzerSDK\Exceptions\UnzerApiException;

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

    /** @var CancelServiceInterface */
    private $cancelService;

    /** @var ShipServiceInterface */
    private $shipService;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        EntityRepositoryInterface $orderTransactionRepository,
        PaymentResourceHydratorInterface $hydrator,
        CancelServiceInterface $cancelService,
        ShipServiceInterface $shipService,
        LoggerInterface $logger
    ) {
        $this->clientFactory              = $clientFactory;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->hydrator                   = $hydrator;
        $this->cancelService              = $cancelService;
        $this->shipService                = $shipService;
        $this->logger                     = $logger;
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
        try {
            $result = $this->shipService->shipTransaction($orderTransactionId, $context);
        } catch (UnzerApiException $exception) {
            $this->logger->error(sprintf('Error while executing shipping notification for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()), [
                'trace' => $exception->getTrace(),
            ]);
            $result = [
                    'status'  => false,
                    'message' => $exception->getMerchantMessage(),
                ];
        } catch (Throwable $exception) {
            $this->logger->error(sprintf('Error while executing shipping notification for order transaction [%s]: %s', $orderTransactionId, $exception->getMessage()), [
                'trace' => $exception->getTrace(),
            ]);
            $result = [
                    'status'  => false,
                    'message' => 'generic-error',
                ];
        }

        if ($result['status'] === false) {
            return new JsonResponse($result, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result);
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
}
