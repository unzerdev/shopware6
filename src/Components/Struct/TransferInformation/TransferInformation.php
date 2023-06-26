<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\TransferInformation;

use Shopware\Core\Framework\Struct\Struct;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;

class TransferInformation extends Struct
{
    /** @var null|string */
    protected $iban;

    /** @var null|string */
    protected $bic;

    /** @var null|string */
    protected $holder;

    /** @var null|string */
    protected $descriptor;

    /** @var null|float */
    protected $amount;

    /**
     * @param Authorization|Charge $payment
     */
    public function __construct(AbstractTransactionType $payment)
    {
        $this->iban       = $payment->getIban();
        $this->bic        = $payment->getBic();
        $this->holder     = $payment->getHolder();
        $this->descriptor = $payment->getDescriptor();

        /** @var float $amount */
        $amount       = $payment->getAmount();
        $this->amount = round($amount, 2);
    }

    public function getEntityData(): array
    {
        return [
            'iban'       => $this->getIban(),
            'bic'        => $this->getBic(),
            'holder'     => $this->getHolder(),
            'descriptor' => $this->getDescriptor(),
            'amount'     => $this->getAmount(),
        ];
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): self
    {
        $this->bic = $bic;

        return $this;
    }

    public function getHolder(): ?string
    {
        return $this->holder;
    }

    public function setHolder(?string $holder): self
    {
        $this->holder = $holder;

        return $this;
    }

    public function getDescriptor(): ?string
    {
        return $this->descriptor;
    }

    public function setDescriptor(?string $descriptor): self
    {
        $this->descriptor = $descriptor;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }
}
