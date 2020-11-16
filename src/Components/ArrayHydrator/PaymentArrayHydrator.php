<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ArrayHydrator;

use heidelpayPHP\Resources\EmbeddedResources\Amount;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\HirePurchaseDirectDebit;
use heidelpayPHP\Resources\PaymentTypes\InvoiceGuaranteed;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Resources\TransactionTypes\Shipment;
use Psr\Log\LoggerInterface;
use Throwable;

class PaymentArrayHydrator implements PaymentArrayHydratorInterface
{
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function hydrateArray(Payment $resource): array
    {
        $authorization = $resource->getAuthorization();
        $paymentType   = $resource->getPaymentType();

        $data = array_merge($resource->expose(), [
            'state' => [
                'name' => $resource->getStateName(),
                'id'   => $resource->getState(),
            ],
            'currency'          => $resource->getCurrency(),
            'authorization'     => $authorization ? $authorization->expose() : null,
            'basket'            => $resource->getBasket() ? $resource->getBasket()->expose() : null,
            'customer'          => $resource->getCustomer() ? $resource->getCustomer()->expose() : null,
            'metadata'          => [],
            'isShipmentAllowed' => $paymentType instanceof InvoiceGuaranteed || $paymentType instanceof HirePurchaseDirectDebit,
            'type'              => $paymentType ? $paymentType->expose() : null,
            'amount'            => $this->hydrateAmount($resource->getAmount()),
            'charges'           => [],
            'shipments'         => [],
            'cancellations'     => [],
            'transactions'      => [],
        ]);

        if ($authorization instanceof Authorization) {
            $data['transactions'][] = [
                'type'   => 'authorization',
                'amount' => $authorization->getAmount(),
                'date'   => $authorization->getDate(),
                'id'     => $authorization->getId(),
            ];
        }

        /** @var Charge $metaCharge */
        foreach ($resource->getCharges() as $metaCharge) {
            try {
                /** @var Charge $charge */
                $charge = $resource->getCharge($metaCharge->getId());
            } catch (Throwable $t) {
                $this->logResourceError($t);

                continue;
            }

            $data['charges'][]      = $charge->expose();
            $data['transactions'][] = [
                'type'   => 'charge',
                'amount' => $charge->getAmount(),
                'date'   => $charge->getDate(),
                'id'     => $charge->getId(),
            ];

            if (!array_key_exists('shortId', $data) && $charge->getShortId() !== null) {
                $data['shortId'] = $charge->getShortId();
            }
        }

        /** @var Shipment $metaShipment */
        foreach ($resource->getShipments() as $metaShipment) {
            try {
                /** @var Shipment $shipment */
                $shipment = $resource->getShipment($metaShipment->getId());
            } catch (Throwable $t) {
                $this->logResourceError($t);

                continue;
            }

            $data['shipments'][]    = $shipment->expose();
            $data['transactions'][] = [
                'type'   => 'shipment',
                'amount' => $shipment->getAmount(),
                'date'   => $shipment->getDate(),
                'id'     => $shipment->getId(),
            ];
        }

        /** @var Cancellation $metaCancellation */
        foreach ($resource->getCancellations() as $metaCancellation) {
            try {
                /** @var Cancellation $cancellation */
                $cancellation = $resource->getCancellation($metaCancellation->getId());
            } catch (Throwable $t) {
                $this->logResourceError($t);

                continue;
            }

            $data['cancellations'][] = $cancellation->expose();
            $data['transactions'][]  = [
                'type'   => 'cancellation',
                'amount' => $cancellation->getAmount(),
                'date'   => $cancellation->getDate(),
                'id'     => $cancellation->getId(),
            ];
        }

        if ($resource->getMetadata() !== null) {
            foreach ($resource->getMetadata()->expose() as $key => $value) {
                $data['metadata'][] = [
                    'key'   => $key,
                    'value' => $value,
                ];
            }
        }

        return $data;
    }

    private function hydrateAmount(Amount $amount): array
    {
        return [
            'total'     => $amount->getTotal(),
            'canceled'  => $amount->getCanceled(),
            'charged'   => $amount->getCharged(),
            'remaining' => $amount->getRemaining(),
        ];
    }

    private function logResourceError(Throwable $t): void
    {
        $this->logger->error('PaymentArrayHydration: ' . $t->getMessage(), [
            'file'  => $t->getFile(),
            'line'  => $t->getLine(),
            'trace' => $t->getTraceAsString(),
        ]);
    }
}
