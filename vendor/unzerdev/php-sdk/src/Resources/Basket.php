<?php

namespace UnzerSDK\Resources;

use stdClass;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use function count;

/**
 * This represents the basket resource.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Basket extends AbstractUnzerResource
{
    /**
     * @var float $amountTotalGross
     *
     * @deprecated since 1.1.5.0 @see $totalValueGross.
     */
    protected $amountTotalGross = 0.0;

    /**
     * @var float $amountTotalDiscount
     *
     * @deprecated since 1.1.5.0 @see Please set $amountDiscountPerUnitGross for each element of $basketItems instead.
     */
    protected $amountTotalDiscount = 0.0;

    /**
     * @var float $amountTotalVat
     *
     * @deprecated since 1.1.5.0  Please set the $vat in percent for each element of $basketItems instead, if not already happened. The actual amount is not required anymore.
     */
    protected $amountTotalVat = 0.0;

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
     * Basket constructor.
     *
     * @deprecated since 1.1.5.0 Please call constructor without parameters and use setter functions instead.
     *
     * @param float  $amountTotalGross
     * @param string $currencyCode
     * @param string $orderId
     * @param array  $basketItems
     */
    public function __construct(
        string $orderId = '',
        float $amountTotalGross = 0.0,
        string $currencyCode = 'EUR',
        array $basketItems = []
    ) {
        $this->currencyCode     = $currencyCode;
        $this->orderId          = $orderId;
        $this->setAmountTotalGross($amountTotalGross);
        $this->setBasketItems($basketItems);
    }

    /**
     * @return float
     *
     * @deprecated since 1.1.5.0 @see getTotalValueGross().
     */
    public function getAmountTotalGross(): float
    {
        return $this->amountTotalGross;
    }

    /**
     * @param float $amountTotalGross
     *
     * @deprecated since 1.1.5.0 @see setTotalValueGross().
     *
     * @return Basket
     */
    public function setAmountTotalGross(float $amountTotalGross): Basket
    {
        $this->amountTotalGross = $amountTotalGross;
        return $this;
    }

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
     * @return float
     *
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     */
    public function getAmountTotalDiscount(): float
    {
        return $this->amountTotalDiscount;
    }

    /**
     * @param float $amountTotalDiscount
     *
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     *
     * @return Basket
     */
    public function setAmountTotalDiscount(float $amountTotalDiscount): Basket
    {
        $this->amountTotalDiscount = $amountTotalDiscount;
        return $this;
    }

    /**
     * @return float
     *
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     */
    public function getAmountTotalVat(): float
    {
        return $this->amountTotalVat;
    }

    /**
     * @param float $amountTotalVat
     *
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     *
     * @return Basket
     */
    public function setAmountTotalVat(float $amountTotalVat): Basket
    {
        $this->amountTotalVat = $amountTotalVat;
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

    /**
     * Add the dynamically set meta data.
     * {@inheritDoc}
     */
    public function expose(): array
    {
        $basketItemArray = [];

        /** @var BasketItem $basketItem */
        foreach ($this->getBasketItems() as $basketItem) {
            $basketItemArray[] = $basketItem->expose();
        }

        $returnArray = parent::expose();
        $returnArray['basketItems'] = $basketItemArray;

        return $returnArray;
    }

    /**
     * {@inheritDoc}
     */
    public function getApiVersion(): string
    {
        if (!empty($this->getTotalValueGross())) {
            return 'v2';
        }
        return parent::getApiVersion();
    }

    /**
     * Returns the key of the last BasketItem in the Array.
     *
     * @return int|string|null
     */
    private function getKeyOfLastBasketItemAdded()
    {
        end($this->basketItems);
        return key($this->basketItems);
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'baskets';
    }

    /**
     * {@inheritDoc}
     */
    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        parent::handleResponse($response, $method);

        if (isset($response->basketItems)) {
            $items = [];
            foreach ($response->basketItems as $basketItem) {
                $item = new BasketItem();
                $item->handleResponse($basketItem);
                $items[] = $item;
            }
            $this->setBasketItems($items);
        }
    }
}
