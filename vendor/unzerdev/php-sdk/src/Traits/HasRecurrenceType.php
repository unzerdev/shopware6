<?php

/**
 * This trait adds the short id and unique id property to a class.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

use UnzerSDK\Resources\EmbeddedResources\CardTransactionData;

trait HasRecurrenceType
{
    /**
     * @return string|null
     */
    public function getRecurrenceType(): ?string
    {
        $cardTransactionData = $this->getCardTransactionData();
        if ($cardTransactionData instanceof CardTransactionData) {
            return $cardTransactionData->getRecurrenceType();
        }

        return $this->getAdditionalTransactionData()->card['recurrenceType'] ?? null;
    }

    /**
     * @param string $recurrenceType Recurrence type used for recurring payment.
     *
     * @return $this
     */
    public function setRecurrenceType(string $recurrenceType): self
    {
        $card = $this->getCardTransactionData() ?? new CardTransactionData();
        $card->setRecurrenceType($recurrenceType);
        $this->addAdditionalTransactionData('card', $card);

        return $this;
    }
}
