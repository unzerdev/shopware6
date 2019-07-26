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
    public function validateApiCredentials(string $orderTransaction, Context $context): JsonResponse
    {
        $transaction = $this->getOrderTransaction($orderTransaction, $context);


        $client = $this->clientFactory->createClient('');

        $resource = $client->fetchPaymentByOrderId($orderTransaction);
        $history  = $this->hydrator->hydrateArray($resource);

        return new JsonResponse(['history' => $history]);
    }

    private function getOrderTransaction(string $orderTransaction, Context $context)
    {
        $criteria = new Criteria();

        return $this->orderTransactionRepository->search($criteria, $context);
    }
}
