<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\CancelService;

use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;

class CancelService implements CancelServiceInterface
{
    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    public function __construct(
        EntityRepositoryInterface $orderTransactionRepository,
        ClientFactoryInterface $clientFactory
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->clientFactory              = $clientFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function cancelChargeById(string $orderTransactionId, string $chargeId, float $amountGross, Context $context): void
    {
        $decimalPrecision = 4;

        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getOrder() === null) {
            throw new NotFoundHttpException(); // TODO: change to better exception
        }

        if ($transaction->getOrder()->getCurrency()) {
            $decimalPrecision = min($decimalPrecision, $transaction->getOrder()->getCurrency()->getDecimalPrecision());
        }

        $taxRates = [];

        /** @var CalculatedTax $calculatedTax */
        foreach ($transaction->getAmount()->getCalculatedTaxes() as $calculatedTax) {
            $taxRates[] = $calculatedTax->getTaxRate();
        }

        $clearedTaxRate = array_sum($taxRates) / count($taxRates);

        $roundedAmountGross = (int) round($amountGross * (10 ** $decimalPrecision));
        $roundedAmountNet   = (int) round($roundedAmountGross / (100 + $clearedTaxRate) * 100);
        $roundedAmountVat   = $roundedAmountGross - $roundedAmountNet;
        $amountNet          = $roundedAmountNet / (10 ** $decimalPrecision);
        $amountVat          = $roundedAmountVat / (10 ** $decimalPrecision);

        $client = $this->clientFactory->createClient($transaction->getOrder()->getSalesChannelId());

        $client->cancelChargeById(
            $orderTransactionId,
            $chargeId,
            $amountGross,
            '',
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
}
