<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\PaymentResourceHydrator;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use stdClass;
use Throwable;
use UnzerPayment6\UnzerPayment6;
use UnzerSDK\Resources\EmbeddedResources\Amount;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Resources\PaymentTypes\InvoiceSecured;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Shipment;
use UnzerSDK\Unzer;

class PaymentResourceHydrator implements PaymentResourceHydratorInterface
{
    private const TRANSACTION_TYPE_AUTHORIZATION = 'authorization';
    private const TRANSACTION_TYPE_CANCELLATION  = 'cancellation';
    private const TRANSACTION_TYPE_CHARGE        = 'charge';
    private const TRANSACTION_TYPE_SHIPMENT      = 'shipment';

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function hydrateArray(Payment $payment, OrderTransactionEntity $orderTransaction, Unzer $client): array
    {
        $decimalPrecision = $this->getDecimalPrecision($orderTransaction);
        $data             = $this->getBaseData($payment, $orderTransaction->getPaymentMethodId(), $decimalPrecision);

        try {
            $authorization = $payment->getAuthorization();

            if ($authorization instanceof Authorization) {
                $data['transactions'][$this->getTransactionKey($authorization)] = $this->hydrateAuthorize($authorization, $decimalPrecision);
                $data['descriptor']                                             = $authorization->getDescriptor();
            }
        } catch (Throwable $throwable) {
            $this->logResourceError($throwable);
        }

        $this->hydrateTransactions($data, $payment, $decimalPrecision, $client);
        $this->validateIsShipmentAllowed($data, $orderTransaction->getOrder());

        if ($payment->getMetadata() !== null) {
            $exposedMeta = $payment->getMetadata()->expose();

            if ($exposedMeta instanceof stdClass) {
                $encoded = json_encode($exposedMeta);

                if (!$encoded) {
                    return $data;
                }

                $exposedMeta = json_decode($encoded, true);

                if (!is_array($exposedMeta) || empty($exposedMeta)) {
                    return $data;
                }
            }

            foreach ($exposedMeta as $key => $value) {
                $data['metadata'][] = compact('key', 'value');
            }
        }

        return $data;
    }

    protected function getBaseData(Payment $payment, string $paymentMethodId, int $decimalPrecision): array
    {
        $paymentType = $payment->getPaymentType();

        $exposedPayment = $payment->expose();

        if ($exposedPayment instanceof stdClass) {
            $encoded = json_encode($exposedPayment);

            if (!$encoded) {
                $exposedPayment = [];
            } else {
                $exposedPayment = json_decode($encoded, true);

                if (!is_array($exposedPayment) || empty($exposedPayment)) {
                    $exposedPayment = [];
                }
            }
        }

        return array_merge(
            $exposedPayment,
            [
                'state' => [
                    'name' => $payment->getStateName(),
                    'id'   => $payment->getState(),
                ],
                'currency'          => $payment->getCurrency(),
                'basket'            => $payment->getBasket() ? $payment->getBasket()->expose() : null,
                'customer'          => $payment->getCustomer() ? $payment->getCustomer()->expose() : null,
                'metadata'          => [],
                'isShipmentAllowed' => $paymentType instanceof InvoiceSecured || $paymentType instanceof InstallmentSecured,
                'type'              => $paymentType ? $paymentType->expose() : null,
                'amount'            => $this->hydrateAmount($payment->getAmount(), $decimalPrecision),
                'transactions'      => [],
                'paymentMethodId'   => $paymentMethodId,
            ]
        );
    }

    protected function hydrateTransactions(array &$data, Payment $payment, int $decimalPrecision, Unzer $client): void
    {
        $this->hydrateCharges($data, $payment, $decimalPrecision);
        $this->hydrateRefunds($data, $payment, $decimalPrecision, $client);
        $totalShippingAmount = $this->hydrateShipments($data, $payment, $decimalPrecision);

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

    protected function hydrateCharges(array &$data, Payment $payment, int $decimalPrecision): void
    {
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

                $data['transactions'][$this->getTransactionKey($cancellation)] = $this->hydrateTransactionItem(
                    $cancellation,
                    self::TRANSACTION_TYPE_CANCELLATION,
                    $decimalPrecision
                );
            }
        }
    }

    protected function hydrateRefunds(array &$data, Payment $payment, int $decimalPrecision, Unzer $client): void
    {
        /** @var Cancellation $lazyRefund */
        foreach ($payment->getRefunds() as $lazyRefund) {
            try {
                $cancellation = $client->fetchPaymentRefund($payment, $lazyRefund->getId());
            } catch (Throwable $throwable) {
                $this->logResourceError($throwable);

                continue;
            }

            $item = $this->hydrateTransactionItem(
                $cancellation,
                self::TRANSACTION_TYPE_CANCELLATION,
                $decimalPrecision
            );

            // Refunds aren't linked to a specific charge, so we have to reduce all the charges
            foreach ($data['transactions'] as &$transaction) {
                if ($transaction['type'] !== self::TRANSACTION_TYPE_CHARGE) {
                    continue;
                }

                $transaction['remainingAmount'] = round($payment->getAmount()->getCharged() * (10 ** $decimalPrecision));
            }
            unset($transaction);

            $data['transactions'][$this->getTransactionKey($cancellation)] = $item;
        }
    }

    protected function hydrateShipments(array &$data, Payment $payment, int $decimalPrecision): float
    {
        $totalShippingAmount = 0;

        /** @var Shipment $lazyShipment */
        foreach ($payment->getShipments() as $lazyShipment) {
            try {
                /** @var Shipment $shipment */
                $shipment = $payment->getShipment($lazyShipment->getId());
            } catch (Throwable $throwable) {
                $this->logResourceError($throwable);

                continue;
            }

            $data['transactions'][$this->getTransactionKey($shipment)] = $this->hydrateTransactionItem(
                $shipment,
                self::TRANSACTION_TYPE_SHIPMENT,
                $decimalPrecision
            );

            if ($shipment->getAmount()) {
                $totalShippingAmount += round($shipment->getAmount() * (10 ** $decimalPrecision));
            }
        }

        return $totalShippingAmount;
    }

    protected function validateIsShipmentAllowed(array &$data, ?OrderEntity $orderEntity): void
    {
        if ($data['isShipmentAllowed'] === false) {
            return;
        }

        if ($orderEntity === null) {
            return;
        }

        $orderDocuments = $orderEntity->getDocuments();

        if ($orderDocuments === null) {
            return;
        }

        $filteredDocuments = $orderDocuments->filter(static function (DocumentEntity $entity) {
            if ($entity->getDocumentType() && $entity->getDocumentType()->getTechnicalName() === 'invoice') {
                return $entity;
            }

            return null;
        });

        /** Set `isShipmentAllowed` to false due to missing invoice */
        $data['isShipmentAllowed'] = false;

        if ($filteredDocuments->count() <= 0) {
            return;
        }

        /** @var DocumentEntity $filteredDocument */
        foreach ($filteredDocuments as $filteredDocument) {
            $documentConfig = $filteredDocument->getConfig();

            if (array_key_exists('documentNumber', $documentConfig) && !empty($documentConfig['documentNumber'])) {
                $data['isShipmentAllowed'] = true;
            }
        }
    }

    protected function hydrateAmount(Amount $amount, int $decimalPrecision): array
    {
        return [
            'decimalPrecision' => $decimalPrecision,
            'total'            => (int) round($amount->getTotal() * (10 ** $decimalPrecision)),
            'cancelled'        => (int) round($amount->getCanceled() * (10 ** $decimalPrecision)),
            'charged'          => (int) round($amount->getCharged() * (10 ** $decimalPrecision)),
            'remaining'        => (int) round($amount->getRemaining() * (10 ** $decimalPrecision)),
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
        $data = $this->hydrateTransactionItem($charge, self::TRANSACTION_TYPE_CHARGE, $decimalPrecision);

        if ($charge->getCancelledAmount() !== null) {
            $chargedAmount   = (int) round($charge->getAmount() * (10 ** $decimalPrecision));
            $cancelledAmount = (int) round($charge->getCancelledAmount() * (10 ** $decimalPrecision));
            $reducedAmount   = $chargedAmount - $cancelledAmount;

            $data['processedAmount'] = $cancelledAmount;
            $data['remainingAmount'] = $reducedAmount;
        }

        return $data;
    }

    protected function hydrateAuthorize(Authorization $authorization, int $decimalPrecision): array
    {
        $data = $this->hydrateTransactionItem(
            $authorization,
            self::TRANSACTION_TYPE_AUTHORIZATION,
            $decimalPrecision
        );

        $payment = $authorization->getPayment();

        if ($payment !== null) {
            $amount = $payment->getAmount();

            $authorizedAmount = (int) round($authorization->getAmount() * (10 ** $decimalPrecision));
            $remainingAmount  = (int) round($amount->getRemaining() * (10 ** $decimalPrecision));
            $reducedAmount    = $authorizedAmount - $remainingAmount;

            $data['processedAmount'] = $reducedAmount;
            $data['remainingAmount'] = $remainingAmount;
        }

        return $data;
    }

    protected function hydrateTransactionItem(AbstractTransactionType $item, string $type, int $decimalPrecision): array
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
            'amount'  => (int) round(($amount * (10 ** $decimalPrecision))),
        ];
    }

    protected function getDecimalPrecision(?OrderTransactionEntity $orderTransaction): int
    {
        if ($orderTransaction === null
            || $orderTransaction->getOrder() === null
            || $orderTransaction->getOrder()->getCurrency() === null) {
            return UnzerPayment6::MAX_DECIMAL_PRECISION;
        }

        return min(
            $orderTransaction->getOrder()->getCurrency()->getItemRounding()->getDecimals(),
            UnzerPayment6::MAX_DECIMAL_PRECISION
        );
    }

    protected function logResourceError(Throwable $t): void
    {
        $this->logger->error(
            sprintf('Error while preparing payment data: %s', $t->getMessage()),
            [
                'file'  => $t->getFile(),
                'line'  => $t->getLine(),
                'trace' => $t->getTraceAsString(),
            ]
        );
    }
}
