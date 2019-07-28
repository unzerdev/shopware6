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
     * @Route("/api/v{version}/_action/heidelpay/transaction/{orderTransaction}/details", name="api.action.heidelpay.transaction.details", methods={"GET"})
     */
    public function fetchTransactionDetails(string $orderTransaction, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransaction, $context);

        if (null === $transaction) {
            throw new NotFoundHttpException();
        }

        if (null === $transaction->getOrder()) {
            throw new NotFoundHttpException();
        }

        $client = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());

        try {
            $resource = $client->fetchPaymentByOrderId($orderTransaction);
            $data     = $this->hydrator->hydrateArray($resource);
        } catch (Throwable $exception) {
            throw $exception; // TODO: handle error or pass to administration
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/v{version}/_action/heidelpay/transaction/{orderTransaction}/charge/{amount}", name="api.action.heidelpay.transaction.charge", methods={"GET"})
     */
    public function chargeTransaction(string $orderTransaction, float $amount, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransaction, $context);

        if (null === $transaction) {
            throw new NotFoundHttpException();
        }

        if (null === $transaction->getOrder()) {
            throw new NotFoundHttpException();
        }

        $client = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());

        try {
            $client->chargeAuthorization($orderTransaction, $amount);
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
     * @Route("/api/v{version}/_action/heidelpay/transaction/{orderTransaction}/refund/{charge}/{amount}", name="api.action.heidelpay.transaction.refund", methods={"GET"})
     */
    public function refundTransaction(string $orderTransaction, string $charge, float $amount, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransaction, $context);

        if (null === $transaction) {
            throw new NotFoundHttpException();
        }

        if (null === $transaction->getOrder()) {
            throw new NotFoundHttpException();
        }

        $client = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());

        try {
            $client->cancelChargeById($orderTransaction, $charge, $amount);
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
     * @Route("/api/v{version}/_action/heidelpay/transaction/{orderTransaction}/ship", name="api.action.heidelpay.transaction.ship", methods={"GET"})
     */
    public function shipTransaction(string $orderTransaction, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransaction, $context);

        if (null === $transaction) {
            throw new NotFoundHttpException();
        }

        if (null === $transaction->getOrder()) {
            throw new NotFoundHttpException();
        }

        $client = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());

        try {
            $client->ship($orderTransaction);
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
