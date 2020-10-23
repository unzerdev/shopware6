<?php

declare(strict_types=1);

namespace UnzerPayment6\DataAbstractionLayer\Entity\TransferInfo;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class UnzerPaymentTransferInfoEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $transactionId;

    /** @var string */
    protected $iban;

    /** @var string */
    protected $bic;

    /** @var string */
    protected $holder;

    /** @var string */
    protected $descriptor;

    /** @var float */
    protected $amount;

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getIban(): string
    {
        return $this->iban;
    }

    public function setIban(string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    public function getBic(): string
    {
        return $this->bic;
    }

    public function setBic(string $bic): self
    {
        $this->bic = $bic;

        return $this;
    }

    public function getHolder(): string
    {
        return $this->holder;
    }

    public function setHolder(string $holder): self
    {
        $this->holder = $holder;

        return $this;
    }

    public function getDescriptor(): string
    {
        return $this->descriptor;
    }

    public function setDescriptor(string $descriptor): self
    {
        $this->descriptor = $descriptor;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }
}
