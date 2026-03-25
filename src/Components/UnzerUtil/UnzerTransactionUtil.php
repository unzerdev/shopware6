<?php declare(strict_types=1);

namespace UnzerPayment6\Components\UnzerUtil;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerPayment6\Installer\CustomFieldInstaller;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Unzer;

readonly class UnzerTransactionUtil
{
    public const ORDER_TRANSACTION_ASSOCIATIONS = [
        'order',
        'order.billingAddress.country',
        'order.currency',
        'order.documents.documentType',
        'paymentMethod',
        'order.orderCustomer.customer',
        'order.deliveries.shippingMethod.translated',
        'order.deliveries.shippingOrderAddress.country',
        'order.lineItems.product.manufacturer',
        'order.lineItems.cover.url',
        'order.lineItems.calculatedPrices.taxes',
    ];

    public function __construct(
        protected EntityRepository $orderTransactionRepository,
        protected TransactionStateHandlerInterface $transactionStateHandler,
        protected LoggerInterface $logger,
    ) {
    }

    public function getOrderTransaction(string $orderTransactionId, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociations(self::ORDER_TRANSACTION_ASSOCIATIONS);

        return $this->orderTransactionRepository->search($criteria, $context)->first();
    }

    /**
     * Gets the order transaction for the provided order entity. Only Unzer transactions are considered.
     */
    public function getOrderTransactionFromOrder(OrderEntity $orderEntity, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderEntity->getId()));
        $criteria->addFilter(new EqualsAnyFilter('paymentMethodId', PaymentInstaller::PAYMENT_METHOD_IDS));
        $criteria->addAssociations(self::ORDER_TRANSACTION_ASSOCIATIONS);

        return $this->orderTransactionRepository->search($criteria, $context)->last();
    }

    public function getOrderTransactionFromOrderNumber(string $orderNumber, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('order.orderNumber', $orderNumber));
        $criteria->addFilter(new EqualsAnyFilter('paymentMethodId', PaymentInstaller::PAYMENT_METHOD_IDS));
        $criteria->addAssociations(self::ORDER_TRANSACTION_ASSOCIATIONS);

        return $this->orderTransactionRepository->search($criteria, $context)->last();
    }

    public static function fetchPaymentFromOrderTransaction(OrderTransactionEntity $orderTransaction, Unzer $client): Payment
    {
        try {
            $payment = $client->fetchPaymentByOrderId($orderTransaction->getId());
            // not sure what this is for - we'll leave it here for now:
            $payment = $client->fetchPayment($payment);
        } catch (UnzerApiException $e) {
            $paymentId = $orderTransaction->getCustomFields()[CustomFieldInstaller::UNZER_PAYMENT_PAYMENT_ID_KEY] ?? null;
            if ($paymentId) {
                $payment = $client->fetchPayment($paymentId);
            } else {
                throw new \RuntimeException('no payment found');
            }
        }

        return $payment;
    }

    public function updateOrderTransactionStatus(Unzer $client, OrderTransactionEntity $orderTransaction, Context $context): void
    {
        try {
            $payment = self::fetchPaymentFromOrderTransaction($orderTransaction, $client);
            $this->transactionStateHandler->transformTransactionState(
                $orderTransaction->getId(),
                $payment,
                $context
            );
        } catch (\Throwable $e) {
            $this->logger->error('error updating transaction state from util: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

    public function updateOrderTransaction(array $data, Context $context): void
    {
        $this->orderTransactionRepository->update([$data], $context);
    }
}
