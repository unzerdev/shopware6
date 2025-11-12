<?php

namespace UnzerSDK\Resources\V2;

use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;

trait BasketProperties
{
    /** @var float $totalValueGross */
    protected $totalValueGross = 0.0;

    /** @var string $currencyCode */
    protected $currencyCode;

    /** @var string $orderId */
    protected $orderId = '';

    /** @var string $note */
    protected $note;

    /** @var BasketItem[] $basketItems */
    private $basketItems;

    /**
     * @return float
     */
    public function getTotalValueGross(): float
    {
        return $this->totalValueGross;
    }

    /**
     * @param float $totalValueGross
     *
     * @return Basket
     */
    public function setTotalValueGross(float $totalValueGross): Basket
    {
        $this->totalValueGross = $totalValueGross;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    /**
     * @param string $currencyCode
     *
     * @return Basket
     */
    public function setCurrencyCode(string $currencyCode): Basket
    {
        $this->currencyCode = $currencyCode;
        return $this;
    }

    /**
     * @return int
     */
    public function getItemCount(): int
    {
        return count($this->basketItems);
    }

    /**
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param string|null $note
     *
     * @return Basket
     */
    public function setNote(?string $note): Basket
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     *
     * @return Basket
     */
    public function setOrderId(string $orderId): Basket
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return array
     */
    public function getBasketItems(): array
    {
        return $this->basketItems;
    }

    /**
     * @param array $basketItems
     *
     * @return Basket
     */
    public function setBasketItems(array $basketItems): Basket
    {
        $this->basketItems = [];

        foreach ($basketItems as $basketItem) {
            $this->addBasketItem($basketItem);
        }

        return $this;
    }

    /**
     * Adds the given BasketItem to the Basket.
     *
     * @param BasketItem $basketItem
     *
     * @return Basket
     */
    public function addBasketItem(BasketItem $basketItem): Basket
    {
        $this->basketItems[] = $basketItem;
        if ($basketItem->getBasketItemReferenceId() === null) {
            $basketItem->setBasketItemReferenceId((string)$this->getKeyOfLastBasketItemAdded());
        }
        return $this;
    }

    /**
     * @param int $index
     *
     * @return BasketItem|null
     */
    public function getBasketItemByIndex(int $index): ?BasketItem
    {
        return $this->basketItems[$index] ?? null;
    }
}
