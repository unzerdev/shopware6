<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\PaymentResourceHydrator;

use DateTimeImmutable;
use heidelpayPHP\Resources\EmbeddedResources\Amount;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\HirePurchaseDirectDebit;
use heidelpayPHP\Resources\PaymentTypes\InvoiceGuaranteed;
use heidelpayPHP\Resources\TransactionTypes\AbstractTransactionType;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Resources\TransactionTypes\Shipment;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Throwable;
use UnzerPayment6\UnzerPayment6;

class PaymentResourceHydrator implements PaymentResourceHydratorInterface
{
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function hydrateArray(Payment $payment, ?OrderTransactionEntity $orderTransaction): array
    {
        $decimalPrecision = $this->getDecimalPrecision($orderTransaction);
        $data             = $this->getBaseData($payment);

        try {
            $authorization = $payment->getAuthorization();

            if ($authorization instanceof Authorization) {
                $data['transactions'][$this->getTransactionKey($authorization)] = $this->hydrateTransactionItem($authorization, 'authorization');
            }
        } catch (Throwable $throwable) {
            $this->logResourceError($throwable);
        }

        $this->hydrateTransactions($data, $payment, $decimalPrecision);

        if ($payment->getMetadata() !== null) {
            foreach ($payment->getMetadata()->expose() as $key => $value) {
                $data['metadata'][] = compact('key', 'value');
            }
        }

        return $data;
    }

    protected function getBaseData(Payment $payment): array
    {
        $paymentType = $payment->getPaymentType();

        return array_merge(
            $payment->expose(),
            [
                'state' => [
                    'name' => $payment->getStateName(),
                    'id'   => $payment->getState(),
                ],
                'currency'          => $payment->getCurrency(),
                'basket'            => $payment->getBasket() ? $payment->getBasket()->expose() : null,
                'customer'          => $payment->getCustomer() ? $payment->getCustomer()->expose() : null,
                'metadata'          => [],
                'isShipmentAllowed' => $paymentType instanceof InvoiceGuaranteed || $paymentType instanceof HirePurchaseDirectDebit,
                'type'              => $paymentType ? $paymentType->expose() : null,
                'amount'            => $this->hydrateAmount($payment->getAmount()),
                'transactions'      => [],
            ]
        );
    }

    protected function hydrateTransactions(array &$data, Payment $payment, int $decimalPrecision): void
    {
        $totalShippingAmount = 0;

        /** @var Charge $lazyCharge */
        foreach ($payment->getCharges() as $lazyCharge) {
            try {
                /** @var Charge $charge */
                $charge = $payment->getCharge($lazyCharge->getId());
            } catch (Throwable $throwable) {
                $this->logResourceError($throwable);

                continue;
            }

            $data['transactions'][$this->getTransactionKey($charge)] = $this->hydrateCharge($charge, $decimalPrecision);

            /** @var Cancellation $lazyCancellation */
            foreach ($charge->getCancellations() as $lazyCancellation) {
                try {
                    /** @var Cancellation $cancellation */
                    $cancellation = $charge->getCancellation($lazyCancellation->getId());
                } catch (Throwable $throwable) {
                    $this->logResourceError($throwable);

                    continue;
                }

                $data['transactions'][$this->getTransactionKey($cancellation)] = $this->hydrateTransactionItem($cancellation, 'cancellation');
            }
        }

        /** @var Shipment $lazyShipment */
        foreach ($payment->getShipments() as $lazyShipment) {
            try {
                /** @var Shipment $shipment */
                $shipment = $payment->getShipment($lazyShipment->getId());
            } catch (Throwable $throwable) {
                $this->logResourceError($throwable);

                continue;
            }

            $data['transactions'][$this->getTransactionKey($shipment)] = $this->hydrateTransactionItem($shipment, 'shipment');

            if ($shipment->getAmount()) {
                $totalShippingAmount += round($shipment->getAmount() * (10 ** $decimalPrecision));
            }
        }

        if ($totalShippingAmount === round($payment->getAmount()->getTotal() * (10 ** $decimalPrecision))) {
            $data['isShipmentAllowed'] = false;
        }

        foreach (array_reverse($data['transactions'], true) as $transaction) {
            if (array_key_exists('shortId', $transaction) && !empty($transaction['shortId'])) {
                $data['shortId'] = $transaction['shortId'];

                break;
            }
        }

        ksort($data['transactions']);
    }

    protected function hydrateAmount(Amount $amount): array
    {
        return [
            'total'     => $amount->getTotal(),
            'canceled'  => $amount->getCanceled(),
            'charged'   => $amount->getCharged(),
            'remaining' => $amount->getRemaining(),
        ];
    }

    protected function getTransactionKey(AbstractTransactionType $item): string
    {
        $date = '';

        if (!empty($item->getDate())) {
            $date = (new DateTimeImmutable($item->getDate()))->getTimestamp();
        }

        return sprintf('%s_%s', $date, $item->getId());
    }

    protected function hydrateCharge(Charge $charge, int $decimalPrecision): array
    {
        $data = $this->hydrateTransactionItem($charge, 'charge');

        if (!empty($charge->getCancelledAmount())) {
            $amount         = $charge->getAmount() * (10 ** $decimalPrecision) - $charge->getCancelledAmount() * (10 ** $decimalPrecision);
            $data['amount'] = $amount / (10 ** $decimalPrecision);
        }

        return $data;
    }

    protected function hydrateTransactionItem(AbstractTransactionType $item, string $type): array
    {
        $amount = 0.00;

        if ($item instanceof Charge || $item instanceof Authorization || $item instanceof Cancellation || $item instanceof Shipment) {
            $amount = $item->getAmount();
        }

        return [
            'id'      => $item->getId(),
            'shortId' => $item->getShortId(),
            'date'    => $item->getDate(),
            'type'    => $type,
            'amount'  => $amount,
        ];
    }

    protected function getDecimalPrecision(?OrderTransactionEntity $orderTransaction): int
    {
        if ($orderTransaction === null
        || $orderTransaction->getOrder() === null
        || $orderTransaction->getOrder()->getCurrency() === null) {
            return UnzerPayment6::MAX_DECIMAL_PRECISION;
        }

        return min($orderTransaction->getOrder()->getCurrency()->getDecimalPrecision(), UnzerPayment6::MAX_DECIMAL_PRECISION);
    }

    protected function logResourceError(Throwable $t): void
    {
        $this->logger->error(
            sprintf('Error while preparing payment data: %s', $t->getMessage()), [
            'file'  => $t->getFile(),
            'line'  => $t->getLine(),
            'trace' => $t->getTraceAsString(),
        ]);
    }
}
