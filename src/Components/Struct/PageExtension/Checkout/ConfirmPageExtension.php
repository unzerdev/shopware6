<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Struct\PageExtension\Checkout;

use HeidelPayment\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use Shopware\Core\Framework\Struct\Struct;

class ConfirmPageExtension extends Struct
{
    /** @var array<HeidelpayPaymentDeviceEntity> */
    protected $creditCards;

    /** @var bool */
    protected $displayCreditCardSelection;

    public function addCreditCard(HeidelpayPaymentDeviceEntity $creditCard): self
    {
        $this->creditCards[] = $creditCard;

        return $this;
    }

    /**
     * @return array<HeidelpayPaymentDeviceEntity>
     */
    public function getCreditCards(): array
    {
        return $this->creditCards;
    }

    /**
     * @param array<HeidelpayPaymentDeviceEntity> $creditCards
     *
     * @return ConfirmPageExtension
     */
    public function setCreditCards(array $creditCards): self
    {
        $this->creditCards = $creditCards;

        return $this;
    }

    public function getDisplayCreditCardSelection(): bool
    {
        return $this->displayCreditCardSelection;
    }

    public function setDisplayCreditCardSelection(bool $displayCreditCardSelection): self
    {
        $this->displayCreditCardSelection = $displayCreditCardSelection;

        return $this;
    }
}
