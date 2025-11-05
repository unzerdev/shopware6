<?php

namespace UnzerSDK\Resources\EmbeddedResources;

use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\V2\BasketItemProperties as BasketV2ItemProperties;

/**
 * This trait adds amount properties to a class.
 *
 * @link  https://docs.unzer.com/
 *
 */
class BasketItem extends AbstractUnzerResource
{
    use BasketV2ItemProperties;

    /**
     * @var float $amountDiscount
     *
     * @deprecated since 1.1.5.0 @see $amountDiscountPerUnitGross.
     */
    protected $amountDiscount = 0.0;

    /**
     * @var float $amountGross
     *
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     */
    protected $amountGross = 0.0;

    /**
     * @var float $amountVat
     *
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     */
    protected $amountVat = 0.0;

    /**
     * @var float $amountPerUnit
     *
     * @deprecated since 1.1.5.0 @see amountPerUnitGross
     */
    protected $amountPerUnit = 0.0;

    /**
     * @var float $amountNet
     *
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     */
    protected $amountNet = 0.0;

    /**
     * BasketItem constructor.
     *
     * @param string $title
     * @param float $amountNet
     * @param float $amountPerUnit
     * @param int $quantity
     * @deprecated since 1.1.5.0 Please call constructor without parameters and use setter functions instead.
     *
     */
    public function __construct(
        string $title = '',
        float $amountNet = 0.0,
        float $amountPerUnit = 0.0,
        int   $quantity = 1
    )
    {
        $this->title = $title;
        $this->quantity = $quantity;
        $this->setAmountNet($amountNet);
        $this->setAmountPerUnit($amountPerUnit);
    }

    /**
     * @return float
     *
     * @deprecated since 1.1.5.0  @see $getAmountDiscountPerUnitGross.
     */
    public function getAmountDiscount(): float
    {
        return $this->amountDiscount;
    }

    /**
     * @param float $amountDiscount
     *
     * @return BasketItem
     * @deprecated since 1.1.5.0  @see $setAmountDiscountPerUnitGross.
     *
     */
    public function setAmountDiscount(float $amountDiscount): BasketItem
    {
        $this->amountDiscount = $amountDiscount;
        return $this;
    }

    /**
     * @return float
     *
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     */
    public function getAmountGross(): float
    {
        return $this->amountGross;
    }

    /**
     * @param float $amountGross
     *
     * @return BasketItem
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     *
     */
    public function setAmountGross(float $amountGross): BasketItem
    {
        $this->amountGross = $amountGross;
        return $this;
    }


    /**
     * @return float
     *
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     */
    public function getAmountVat(): float
    {
        return $this->amountVat;
    }

    /**
     * @param float $amountVat
     *
     * @return BasketItem
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     *
     */
    public function setAmountVat(float $amountVat): BasketItem
    {
        $this->amountVat = $amountVat;
        return $this;
    }

    /**
     * @return float
     *
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     */
    public function getAmountPerUnit(): float
    {
        return $this->amountPerUnit;
    }

    /**
     * @param float $amountPerUnit
     *
     * @return BasketItem
     * @deprecated since 1.1.5.0 @see setAmountPerUnitGross
     *
     */
    public function setAmountPerUnit(float $amountPerUnit): BasketItem
    {
        $this->amountPerUnit = $amountPerUnit;
        return $this;
    }

    /**
     * @return float
     *
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     */
    public function getAmountNet(): float
    {
        return $this->amountNet;
    }

    /**
     * @param float $amountNet
     *
     * @return BasketItem
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     *
     */
    public function setAmountNet(float $amountNet): BasketItem
    {
        $this->amountNet = $amountNet;
        return $this;
    }
}
