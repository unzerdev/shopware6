<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\TransferInformation;

use heidelpayPHP\Resources\TransactionTypes\Charge;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Uuid\Uuid;

class TransferInformation extends Struct
{
    /** @var string */
    protected $transactionId;

    /** @var string */
    protected $transactionVersionId;

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

    public function __construct(string $transactionId, ?string $transactionVersionId)
    {
        $this->transactionId        = $transactionId;
        $this->transactionVersionId = $transactionVersionId;
    }

    public function getEntityData(): array
    {
        return [
            'transactionId'        => $this->getTransactionId(),
            'transactionVersionId' => $this->getTransactionVersionId(),
            'descriptor'           => $this->getDescriptor(),
            'holder'               => $this->getHolder(),
            'amount'               => $this->getAmount(),
            'iban'                 => $this->getIban(),
            'bic'                  => $this->getBic(),
            'id'                   => Uuid::randomHex(),
        ];
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getTransactionVersionId(): string
    {
        return $this->transactionVersionId;
    }

    public function setTransactionVersionId(string $transactionVersionId): void
    {
        $this->transactionVersionId = $transactionVersionId;
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

    public function fromCharge(Charge $charge): self
    {
        $this->bic        = $charge->getBic();
        $this->iban       = $charge->getIban();
        $this->descriptor = $charge->getDescriptor();
        $this->holder     = $charge->getHolder();
        $this->amount     = round($charge->getAmount(), 2);

        return $this;
    }
}
