<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentActions\Struct;

use Shopware\Core\Framework\Struct\Struct;

class RefundItem extends Struct
{


    public function __construct(
        protected string $id,
        protected int $quantity = 0,
        protected float $amount = 0.0,
        protected int $resetStockQuantity = 0,
        protected ?string $label = null
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['quantity'] ?? 0,
            $data['amount'] ?? 0.0,
            $data['resetStockQuantity'] ?? 0,
            $data['label'] ?? null
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getResetStockQuantity(): int
    {
        return $this->resetStockQuantity;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }
}