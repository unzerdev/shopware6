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
use UnzerPayment6\Components\CancelService\CancelServiceInterface;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerPayment6\Installer\CustomFieldInstaller;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Unzer;

class UnzerTransactionUtil
{
    public function __construct(
        protected EntityRepository $orderTransactionRepository,
        protected ClientFactoryInterface $clientFactory,
        protected TransactionStateHandlerInterface $transactionStateHandler,
        protected CancelServiceInterface $cancelService,
        protected LoggerInterface $logger
    ) {
    }

    public function getOrderTransaction(string $orderTransactionId, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociations([
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
        ]);

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

    /**
     * @throws \Exception
     */
    public function captureOrder(OrderEntity $order, Context $context): bool
    {
        $this->logger->info('Capturing order', ['order' => $order->getId()]);
        $orderTransaction = $this->getOrderTransactionFromOrder($order, $context);

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
        $orderTransaction = $this->getOrderTransactionFromOrder($order, $context);

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
                } catch (\Throwable $e) {
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
                } catch (\Throwable $e) {
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
}
