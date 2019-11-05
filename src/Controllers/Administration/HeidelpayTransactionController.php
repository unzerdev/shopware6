<?php

declare(strict_types=1);

namespace HeidelPayment\Controllers\Administration;

use HeidelPayment\Components\ArrayHydrator\PaymentArrayHydratorInterface;
use HeidelPayment\Components\ClientFactory\ClientFactoryInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class HeidelpayTransactionController extends AbstractController
{
    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    /** @var PaymentArrayHydratorInterface */
    private $hydrator;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        EntityRepositoryInterface $orderTransactionRepository,
        PaymentArrayHydratorInterface $hydrator
    ) {
        $this->clientFactory              = $clientFactory;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->hydrator                   = $hydrator;
    }

    /**
     * @Route("/api/v{version}/_action/heidelpay/transaction/{orderTransactionId}/details", name="api.action.heidelpay.transaction.details", methods={"GET"})
     * @RouteScope(scopes={"api"})
     */
    public function fetchTransactionDetails(string $orderTransactionId, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null) {
            throw new NotFoundHttpException();
        }

        $client = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());

        try {
            $resource = $client->fetchPaymentByOrderId($orderTransactionId);
            $data     = $this->hydrator->hydrateArray($resource);
        } catch (HeidelpayApiException $exception) {
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
     * @Route("/api/v{version}/_action/heidelpay/transaction/{orderTransactionId}/charge/{amount}", name="api.action.heidelpay.transaction.charge", methods={"GET"})
     * @RouteScope(scopes={"api"})
     */
    public function chargeTransaction(string $orderTransactionId, float $amount, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null) {
            throw new NotFoundHttpException();
        }

        $client = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());

        try {
            $client->chargeAuthorization($orderTransactionId, $amount);
        } catch (HeidelpayApiException $exception) {
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
     * @Route("/api/v{version}/_action/heidelpay/transaction/{orderTransactionId}/refund/{chargeId}/{amount}", name="api.action.heidelpay.transaction.refund", methods={"GET"})
     * @RouteScope(scopes={"api"})
     */
    public function refundTransaction(string $orderTransactionId, string $chargeId, float $amount, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null) {
            throw new NotFoundHttpException();
        }

        $client = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());

        try {
            $client->cancelChargeById($orderTransactionId, $chargeId, $amount);
        } catch (HeidelpayApiException $exception) {
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
     * @Route("/api/v{version}/_action/heidelpay/transaction/{orderTransactionId}/ship", name="api.action.heidelpay.transaction.ship", methods={"GET"})
     * @RouteScope(scopes={"api"})
     */
    public function shipTransaction(string $orderTransactionId, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null) {
            throw new NotFoundHttpException();
        }

        $client = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());

        try {
            $client->ship($orderTransactionId);
        } catch (HeidelpayApiException $exception) {
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

    private function getOrderTransaction(string $orderTransaction, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$orderTransaction]);
        $criteria->addAssociation('order');

        return $this->orderTransactionRepository->search($criteria, $context)->first();
    }
}
