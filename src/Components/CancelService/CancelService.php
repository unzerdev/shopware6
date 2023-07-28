<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\CancelService;

use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerPayment6\UnzerPayment6;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Resources\TransactionTypes\Cancellation;

class CancelService implements CancelServiceInterface
{
    private EntityRepository $orderTransactionRepository;

    private ClientFactoryInterface $clientFactory;

    public function __construct(
        EntityRepository $orderTransactionRepository,
        ClientFactoryInterface $clientFactory
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->clientFactory              = $clientFactory;
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

        $clearedTaxRate = count($taxRates) > 0
            ? array_sum($taxRates) / count($taxRates)
            : 0;

        $roundedAmountGross = (int) round($amountGross * (10 ** $decimalPrecision));
        $roundedAmountNet   = (int) round($roundedAmountGross / (100 + $clearedTaxRate) * 100);
        $roundedAmountVat   = $roundedAmountGross - $roundedAmountNet;
        $amountNet          = $roundedAmountNet / (10 ** $decimalPrecision);
        $amountVat          = $roundedAmountVat / (10 ** $decimalPrecision);

        $client = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());

        if ($transaction->getPaymentMethodId() === PaymentInstaller::PAYMENT_ID_PAYLATER_INVOICE) {
            $cancellation = new Cancellation($amountGross);

            $client->cancelChargedPayment(
                $orderTransactionId,
                $cancellation
            );

            return;
        }

        $client->cancelChargeById(
            $orderTransactionId,
            $chargeId,
            $amountGross,
            $this->getCancelReasonCode($reasonCode),
            '',
            $amountNet,
            $amountVat
        );
    }

    protected function getOrderTransaction(string $orderTransactionId, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociations([
            'order',
            'order.currency',
        ]);

        return $this->orderTransactionRepository->search($criteria, $context)->first();
    }

    protected function getCancelReasonCode(?string $reasonCode): string
    {
        return $reasonCode ?? CancelReasonCodes::REASON_CODE_CANCEL;
    }
}
