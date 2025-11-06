<?php

namespace UnzerSDK\Resources;

use stdClass;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Constants\ApiVersions;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use UnzerSDK\Resources\V2\BasketProperties as BasketV2Properties;

/**
 * This represents the basket resource.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Basket extends AbstractUnzerResource
{
    use BasketV2Properties;

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
     * @return Basket
     * @deprecated since 1.1.5.0 @see setTotalValueGross().
     *
     */
    public function setAmountTotalGross(float $amountTotalGross): Basket
    {
        $this->amountTotalGross = $amountTotalGross;
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
     * @return Basket
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     *
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
     * @return Basket
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     *
     */
    public function setAmountTotalVat(float $amountTotalVat): Basket
    {
        $this->amountTotalVat = $amountTotalVat;
        return $this;
    }

    /**
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
            return ApiVersions::V2;
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
