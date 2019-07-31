<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Struct\PageExtension\Checkout;

use HeidelPayment\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use Shopware\Core\Framework\Struct\Struct;

class ConfirmPageExtension extends Struct
{
    /** @var HeidelpayPaymentDeviceEntity[] */
    protected $creditCards = [];

    /** @var bool */
    protected $displayCreditCardSelection;

    public function addCreditCard(HeidelpayPaymentDeviceEntity $creditCard): self
    {
        $this->creditCards[] = $creditCard;

        return $this;
    }

    /**
     * @return HeidelpayPaymentDeviceEntity[]
     */
    public function getCreditCards(): array
    {
        return $this->creditCards;
    }

    /**
     * @param HeidelpayPaymentDeviceEntity[] $creditCards
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
