<?php

declare(strict_types=1);

namespace HeidelPayment\Controllers\Administration;

use HeidelPayment\Components\ArrayHydrator\PaymentArrayHydratorInterface;
use HeidelPayment\Components\ClientFactory\ClientFactoryInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class HeidelpayTransactionHistoryController extends AbstractController
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
     * @Route("/api/v{version}/_action/heidelpay/transaction/{orderTransaction}/history", name="api.action.heidelpay.transaction.history", methods={"GET"})
     */
    public function fetchTransactionHistory(string $orderTransaction, Context $context): JsonResponse
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

            if (null === $resource) {
                throw new NotFoundHttpException();
            }

            $history  = $this->hydrator->hydrateArray($resource);
        } catch (Throwable $exception) {
            throw $exception; // TODO: handle error or pass to administration
        }

        return new JsonResponse(['history' => $history]);
    }

    /**
     * @Route("/api/v{version}/_action/heidelpay/transaction/{orderTransaction}/charge/{amount}", defaults={"amount"=0.0}, name="api.action.heidelpay.transaction.charge", methods={"GET"})
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
            if (empty($amount)) {
                $client->chargeAuthorization($orderTransaction);
            } else {
                $client->chargeAuthorization($orderTransaction, $amount);
            }
        } catch (Throwable $exception) {
            throw $exception; // TODO: handle error or pass to administration
        }

        return new JsonResponse(['status' => true]);
    }

    /**
     * @Route("/api/v{version}/_action/heidelpay/transaction/{orderTransaction}/refund", name="api.action.heidelpay.transaction.refund", methods={"GET"})
     */
    public function refundTransaction(string $orderTransaction, Context $context): JsonResponse
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
            $client->cancel($orderTransaction);
        } catch (Throwable $exception) {
            throw $exception; // TODO: handle error or pass to administration
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
