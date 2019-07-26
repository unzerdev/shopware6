<?php

namespace HeidelPayment\Components\ArrayHydrator;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Resources\TransactionTypes\Shipment;

class PaymentArrayHydrator implements PaymentArrayHydratorInterface
{
    public function hydrateArray(Payment $resource): array
    {
        $authorization = $resource->getAuthorization();

        $data = array_merge($resource->expose(), [
            'state' => [
                'name' => $resource->getStateName(),
                'id'   => $resource->getState(),
            ],
            'currency'      => $resource->getCurrency(),
            'authorization' => $authorization ? $authorization->expose() : null,
            'basket'        => $resource->getBasket() ? $resource->getBasket()->expose() : null,
            'customer'      => $resource->getCustomer() ? $resource->getCustomer()->expose() : null,
            'metadata'      => [],
            'type'          => $resource->getPaymentType() ? $resource->getPaymentType()->expose() : null,
            'amount'        => $resource->getAmount() ? $resource->getAmount()->expose() : null,
            'charges'       => [],
            'shipments'     => [],
            'cancellations' => [],
            'transactions'  => [],
        ]);

        if ($authorization !== null) {
            $data['transactions'][] = [
                'type'   => 'authorization',
                'amount' => $authorization->getAmount(),
                'date'   => $authorization->getDate(),
                'id'     => $authorization->getId(),
            ];
        }

        /** @var Charge $metaCharge */
        foreach ($resource->getCharges() as $metaCharge) {
            /** @var Charge $charge */
            $charge = $resource->getCharge($metaCharge->getId());

            $data['charges'][]      = $charge->expose();
            $data['transactions'][] = [
                'type'   => 'charge',
                'amount' => $charge->getAmount(),
                'date'   => $charge->getDate(),
                'id'     => $charge->getId(),
            ];
        }

        /** @var Shipment $metaShipment */
        foreach ($resource->getShipments() as $metaShipment) {
            /** @var Shipment $shipment */
            $shipment = $resource->getShipment($metaShipment->getId());

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
            /** @var Cancellation $cancellation */
            $cancellation = $resource->getCancellation($metaCancellation->getId());

            $data['cancellations'][] = $cancellation->expose();
            $data['transactions'][]  = [
                'type'   => 'cancellation',
                'amount' => $cancellation->getAmount(),
                'date'   => $cancellation->getDate(),
                'id'     => $cancellation->getId(),
            ];
        }

        foreach ($resource->getMetadata()->expose() as $key => $value) {
            $data['metadata'][] = [
                'key'   => $key,
                'value' => $value,
            ];
        }

        return $data;
    }
}
