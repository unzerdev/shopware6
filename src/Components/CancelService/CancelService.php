<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\CancelService;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerPayment6\Components\UnzerUtil\UnzerTransactionUtil;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerPayment6\UnzerPayment6;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Unzer;

class CancelService implements CancelServiceInterface
{
    private const PAYLATER_PAYMENT_METHODS = [
        PaymentInstaller::PAYMENT_ID_PAYLATER_INVOICE,
        PaymentInstaller::PAYMENT_ID_PAYLATER_INSTALLMENT,
        PaymentInstaller::PAYMENT_ID_PAYLATER_DIRECT_DEBIT_SECURED,
        PaymentInstaller::PAYMENT_ID_KLARNA, // same procedure for Klarna as for UPL!
    ];

    private EntityRepository $orderTransactionRepository;

    private ClientFactoryInterface $clientFactory;

    private LoggerInterface $logger;

    private TransactionStateHandlerInterface $transactionStateHandler;

    public function __construct(
        EntityRepository $orderTransactionRepository,
        ClientFactoryInterface $clientFactory,
        TransactionStateHandlerInterface $transactionStateHandler,
        LoggerInterface $logger
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
        $this->transactionStateHandler = $transactionStateHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function cancelChargeById(string $orderTransactionId, string $chargeId, float $amountGross, ?string $reasonCode, Context $context): void
    {
        $decimalPrecision = UnzerPayment6::MAX_DECIMAL_PRECISION;

        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null) {
            throw new InvalidTransactionException($orderTransactionId);
        }

        if ($transaction->getOrder()->getCurrency()) {
            $decimalPrecision = min($decimalPrecision, $transaction->getOrder()->getCurrency()->getItemRounding()->getDecimals());
        }

        $taxRates = [];

        /** @var CalculatedTax $calculatedTax */
        foreach ($transaction->getAmount()->getCalculatedTaxes() as $calculatedTax) {
            $taxRates[] = $calculatedTax->getTaxRate();
        }

        $clearedTaxRate = \count($taxRates) > 0
            ? array_sum($taxRates) / \count($taxRates)
            : 0;

        $roundedAmountGross = (int) round($amountGross * (10 ** $decimalPrecision));
        $roundedAmountNet = (int) round($roundedAmountGross / (100 + $clearedTaxRate) * 100);
        $roundedAmountVat = $roundedAmountGross - $roundedAmountNet;
        $amountNet = $roundedAmountNet / (10 ** $decimalPrecision);
        $amountVat = $roundedAmountVat / (10 ** $decimalPrecision);

        $client = $this->clientFactory->createClient(KeyPairContext::createFromOrderTransaction($transaction));
        $payment = UnzerTransactionUtil::fetchPaymentFromOrderTransaction($transaction, $client);

        if ($this->isPaylaterPaymentMethod($transaction->getPaymentMethodId())) {
            $cancellation = new Cancellation($amountGross);

            $client->cancelChargedPayment(
                $payment,
                $cancellation
            );
        } else {
            $client->cancelChargeById(
                $payment,
                $chargeId,
                $amountGross,
                $this->getCancelReasonCode($reasonCode),
                '',
                $amountNet,
                $amountVat
            );
        }
        $this->updateOrderStatus($client, $transaction, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function cancelAuthorizationById(string $orderTransactionId, string $paymentId, float $amountGross, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null) {
            throw new InvalidTransactionException($orderTransactionId);
        }

        $client = $this->clientFactory->createClient(KeyPairContext::createFromOrderTransaction($transaction));
        $authorization = $client->fetchAuthorization($paymentId);

        if ($this->isPaylaterPaymentMethod($transaction->getPaymentMethodId())) {
            $this->logger->info('Canceling authorization by payment', ['authorization' => $authorization->getPayment()]);
            $client->cancelAuthorizedPayment($authorization->getPayment(), new Cancellation($amountGross));
        } else {
            $this->logger->info('Canceling authorization', ['authorization' => $authorization]);
            $authorization->cancel($amountGross);
        }
        $this->updateOrderStatus($client, $transaction, $context);
    }

    protected function getOrderTransaction(string $orderTransactionId, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociations([
            'order',
            'order.billingAddress',
            'order.currency',
            'paymentMethod',
        ]);

        return $this->orderTransactionRepository->search($criteria, $context)->first();
    }

    protected function getCancelReasonCode(?string $reasonCode): string
    {
        return $reasonCode ?? CancelReasonCodes::REASON_CODE_CANCEL;
    }

    protected function isPaylaterPaymentMethod(string $paymentMethodId): bool
    {
        return \in_array($paymentMethodId, self::PAYLATER_PAYMENT_METHODS, true);
    }

    private function updateOrderStatus(Unzer $client, OrderTransactionEntity $orderTransaction, Context $context): void
    {
        try {
            $payment = UnzerTransactionUtil::fetchPaymentFromOrderTransaction($orderTransaction, $client);
            $this->transactionStateHandler->transformTransactionState(
                $orderTransaction->getId(),
                $payment,
                $context
            );
        } catch (\Throwable $e) {
            $this->logger->error('error updating transaction state after cancel action: ' . $e->getMessage());
        }
    }
}
