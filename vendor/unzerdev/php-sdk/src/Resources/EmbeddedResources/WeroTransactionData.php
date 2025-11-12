<?php

namespace UnzerSDK\Resources\EmbeddedResources;

use stdClass;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\AbstractUnzerResource;

/*
 * Represents `wero` object of `additionalTransactionData`.
 */

class WeroTransactionData extends AbstractUnzerResource
{
    /** @var WeroEventDependentPayment|null $eventDependentPayment */
    protected ?WeroEventDependentPayment $eventDependentPayment = null;

    /**
     * @return WeroEventDependentPayment|null
     */
    public function getEventDependentPayment(): ?WeroEventDependentPayment
    {
        return $this->eventDependentPayment;
    }

    /**
     * @param WeroEventDependentPayment|null $eventDependentPayment
     * @return WeroTransactionData
     */
    public function setEventDependentPayment(?WeroEventDependentPayment $eventDependentPayment): WeroTransactionData
    {
        $this->eventDependentPayment = $eventDependentPayment;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function handleResponse($response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        parent::handleResponse($response, $method);
        if ($response instanceof stdClass && isset($response->eventDependentPayment)) {
            $edp = $this->getEventDependentPayment() ?? new WeroEventDependentPayment();
            $edp->handleResponse($response->eventDependentPayment);
            $this->setEventDependentPayment($edp);
        }
    }
}
