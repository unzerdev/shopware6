<?php

namespace UnzerPayment6\Components\PaymentActions;

use Kiener\MolliePayments\Components\RefundManager\DAL\RefundItem\RefundItemEntity;
use Psr\Log\LoggerInterface;
use Shopware\Commercial\ReturnManagement\Entity\OrderReturn\OrderReturnEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Throwable;
use UnzerPayment6\Components\CancelService\CancelServiceInterface;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\PaymentActions\Struct\RefundItem;
use UnzerPayment6\Components\PaymentActions\Struct\RefundItemCollection;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerPayment6\Components\UnzerUtil\UnzerTransactionUtil;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\Charge;

class PaymentActionService{
    public function __construct(
        protected EntityRepository                 $orderTransactionRepository,
        protected ClientFactoryInterface           $clientFactory,
        protected TransactionStateHandlerInterface $transactionStateHandler,
        protected CancelServiceInterface           $cancelService,
        private EntityRepository                   $productRepository,
        private EntityRepository                   $orderLineItemRepository,
        protected LoggerInterface                  $logger,
        protected UnzerTransactionUtil              $unzerTransactionUtil,
        protected ?EntityRepository $orderReturnRepository = null
    )
    {
    }


    /**
     * @throws \Exception
     */
    public function captureOrder(OrderEntity $order, Context $context): bool
    {
        $this->logger->info('Capturing order', ['order' => $order->getId()]);
        $orderTransaction = $this->unzerTransactionUtil->getOrderTransactionFromOrder($order, $context);

        if ($orderTransaction === null) {
            return false;
        }

        $client = $this->clientFactory->createClient(KeyPairContext::createFromOrderTransaction($orderTransaction));
        try {
            $charge = $client->performChargeOnPayment($orderTransaction->getId(), new Charge($orderTransaction->getAmount()->getTotalPrice()));
            $this->transactionStateHandler->transformTransactionState(
                $orderTransaction->getId(),
                $charge->getPayment(),
                $context
            );
        } catch (UnzerApiException $e) {
            throw new \Exception($e->getMerchantMessage() ?: $e->getClientMessage());
        }

        return true;
    }




    /**
     * @throws \Exception
     */
    public function refundOrder(OrderEntity $order, Context $context): void
    {
        $this->logger->info('Refunding order', ['order' => $order->getId()]);
        $orderTransaction = $this->unzerTransactionUtil->getOrderTransactionFromOrder($order, $context);

        if ($orderTransaction === null) {
            return;
        }

        $client = $this->clientFactory->createClient(KeyPairContext::createFromOrderTransaction($orderTransaction));
        try {
            $payment = $client->fetchPayment($orderTransaction->getId());
            foreach ($payment->getCharges() as $charge) {
                try {
                    if ($charge->isError()) {
                        continue;
                    }
                    $this->logger->info('Refunding charge', ['chargeId' => $charge->getId()]);
                    $this->cancelService->cancelChargeById(
                        $orderTransaction->getId(),
                        $charge->getId(),
                        $charge->getAmount() - $charge->getCancelledAmount(),
                        null,
                        $context
                    );
                } catch (Throwable $e) {
                    $this->logger->error('Error while refunding charge', ['charge' => $charge->getId(), 'error' => $e->getMessage()]);
                }
            }
            $authorization = $payment->getAuthorization();
            if ($authorization !== null && !$authorization->isError()) {
                try {
                    $this->logger->info('Refunding authorization', ['paymentId' => $payment->getId(), 'authorizationId' => $authorization->getId()]);

                    $this->cancelService->cancelAuthorizationById(
                        $orderTransaction->getId(),
                        $payment->getId(),
                        $authorization->getAmount() - $authorization->getCancelledAmount(),
                        $context
                    );
                } catch (Throwable $e) {
                    $this->logger->error('Error while refunding authorization', ['authorization' => $authorization->getId(), 'error' => $e->getMessage()]);
                }
            }
            $this->transactionStateHandler->transformTransactionState(
                $orderTransaction->getId(),
                $payment,
                $context
            );
        } catch (UnzerApiException $e) {
            throw new \Exception($e->getMerchantMessage() ?: $e->getClientMessage());
        }
    }



    public function executeReturnRefunds(OrderEntity $order, Context $context): void
    {
        $this->logger->info('Refunding order based on returns', ['order' => $order->getId()]);

        if($this->orderReturnRepository === null) {
            $this->logger->warning('Returns repository does not exist');
            return;
        }

        $orderTransaction = $this->unzerTransactionUtil->getOrderTransactionFromOrder($order, $context);

        if ($orderTransaction === null) {
            return;
        }

        $returnsToProcess = $this->getUnprocessedReturns($orderTransaction, $context);

        foreach($returnsToProcess as $returnEntity) {
            $items = new RefundItemCollection();
            foreach ($returnEntity->getLineItems() as $lineItem) {
                $refundItem = new RefundItem(
                    id: $lineItem->getOrderLineItemId(),
                    quantity: $lineItem->getQuantity(),
                    amount: $lineItem->getRefundAmount(),
                    resetStockQuantity: 0,
                    label: $lineItem->getLineItem()->getLabel(),
                );
                $items->add($refundItem);
            }
            $cancellationId = $this->doUnifiedRefund(
                orderTransaction: $orderTransaction,
                amount: $returnEntity->getAmountTotal(),
                context: $context,
                items: $items,
                comment: 'SW Auto Refund from order #'.$order->getOrderNumber().' return #'.$returnEntity->getReturnNumber(),
                referenceText: $order->getOrderNumber().'/'.$returnEntity->getReturnNumber()
            );

            $transactionCustomFields = $orderTransaction->getCustomFields() ?? [];
            if (!isset($transactionCustomFields['unzerRefundDetails']['processedReturns'])) {
                $transactionCustomFields['unzerRefundDetails']['processedReturns'] = [];
            }
            $transactionCustomFields['unzerRefundDetails']['processedReturns'][$returnEntity->getId()] = $cancellationId;

            $this->unzerTransactionUtil->updateOrderTransaction([
                'id' => $orderTransaction->getId(),
                'customFields' => $transactionCustomFields,
            ], $context);

            $orderTransaction->setCustomFields($transactionCustomFields);

            $existingComment = $returnEntity->getInternalComment() ?? '';
            $refundNote = 'Unzer refund processed: ' . $cancellationId . ' (' . (new \DateTimeImmutable())->format('Y-m-d H:i:s') . ')';
            $newComment = $existingComment ? $existingComment . "\n\n" . $refundNote : $refundNote;

            $this->orderReturnRepository->update([
                [
                    'id' => $returnEntity->getId(),
                    'internalComment' => $newComment,
                ],
            ], $context);
        }

    }

    /**
     * @return OrderReturnEntity[]
     */
    protected function getUnprocessedReturns(OrderTransactionEntity $orderTransaction, Context $context): array
    {
        if ($this->orderReturnRepository === null) {
            return [];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderTransaction->getOrderId()));
        $criteria->addAssociation('lineItems.lineItem.orderLineItem');

        $returns = $this->orderReturnRepository->search($criteria, $context);

        $transactionCustomFields = $orderTransaction->getCustomFields() ?? [];
        $processedReturns = $transactionCustomFields['unzerRefundDetails']['processedReturns'] ?? [];

        $unprocessedReturns = [];

        foreach ($returns as $return) {
            if (isset($processedReturns[$return->getId()])) {
                continue;
            }

            $unprocessedReturns[] = $return;
        }

        return $unprocessedReturns;
    }

    public function doUnifiedRefund(OrderTransactionEntity $orderTransaction, float $amount, Context $context, ?RefundItemCollection $items = null, string $comment = '', string $referenceText = ''): string
    {
        $client = $this->clientFactory->createClient(KeyPairContext::createFromOrderTransaction($orderTransaction));
        $payment = UnzerTransactionUtil::fetchPaymentFromOrderTransaction($orderTransaction, $client);
        $charges = $payment->getCharges();
        $charge = reset($charges);//TODO

        $cancellation = $this->cancelService->cancelChargeById($orderTransaction->getId(), $charge->getId(), $amount, null, $context, $referenceText);

        $transactionCustomFields = $orderTransaction->getCustomFields() ?? [];
        if (!isset($transactionCustomFields['unzerRefundDetails'])) {
            $transactionCustomFields['unzerRefundDetails'] = [];
        }

        $transactionCustomFields['unzerRefundDetails'][$cancellation->getId()] = [
            'items' => $items?$items->jsonSerialize():[],
            'comment' => $comment,
            'cancellation' => $cancellation->expose(),
        ];

        $this->unzerTransactionUtil->updateOrderTransaction([
            'id' => $orderTransaction->getId(),
            'customFields' => $transactionCustomFields,
        ], $context);

        $orderTransaction->setCustomFields($transactionCustomFields);

        if($items !== null && $items->count() > 0) {
            $this->processRefundItems($items, $context);
        }

        return $cancellation->getId();
    }


    protected function processRefundItems(RefundItemCollection $items, Context $context)
    {
        foreach ($items->getElements() as $item) {
            $lineItemId = $item->getId();
            $quantity = $item->getQuantity();
            $amount = $item->getAmount();
            $restockQuantity = $item->getResetStockQuantity();

            if ($quantity <= 0 && $restockQuantity <= 0) {
                continue;
            }

            /** @var OrderLineItemEntity $lineItem */
            $lineItem = $this->orderLineItemRepository->search(new Criteria([$lineItemId]), $context)->first();

            if ($lineItem === null) {
                continue;
            }

            if ($restockQuantity > 0 && $lineItem->getProductId()) {
                /** @var ProductEntity $product */
                $product = $this->productRepository->search(new Criteria([$lineItem->getProductId()]), $context)->first();
                if ($product !== null) {
                    $updatePayload = [
                        'id' => $product->getId(),
                        'quantity' => $product->getStock() + $restockQuantity,
                    ];
                    $this->productRepository->update([$updatePayload], $context);
                }
            }
            if ($quantity > 0) {
                $customFields = $lineItem->getCustomFields();
                if (!is_array($customFields)) {
                    $customFields = [];
                }

                $customFields['unzerRefundedQuantity'] = ($customFields['unzerRefundedQuantity'] ?? 0) + $quantity;
                $customFields['unzerRefundedAmount'] = ($customFields['unzerRefundedAmount'] ?? 0) + $amount;
                $updatePayload = [
                    'id' => $lineItem->getId(),
                    'customFields' => $customFields,
                ];
                $this->orderLineItemRepository->update([$updatePayload], $context);
            }
        }
    }
}