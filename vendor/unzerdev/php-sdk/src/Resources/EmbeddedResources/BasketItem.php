<?php

namespace UnzerSDK\Resources\EmbeddedResources;

use UnzerSDK\Resources\AbstractUnzerResource;

/**
 * This trait adds amount properties to a class.
 *
 * @link  https://docs.unzer.com/
 *
 */
class BasketItem extends AbstractUnzerResource
{
    /** @var string $basketItemReferenceId */
    protected $basketItemReferenceId;

    /** @var int $quantity */
    protected $quantity = 1;

    /** @var float $vat */
    protected $vat = 0.0;

    /**
     * @var float $amountDiscount
     *
     * @deprecated since 1.1.5.0 @see $amountDiscountPerUnitGross.
     */
    protected $amountDiscount = 0.0;

    /** @var float $amountDiscountPerUnitGross */
    protected $amountDiscountPerUnitGross = 0.0;

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

    /** @var float $amountPerUnitGross */
    protected $amountPerUnitGross = 0.0;

    /**
     * @var float $amountNet
     *
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     */
    protected $amountNet = 0.0;

    /** @var string $unit */
    protected $unit;

    /** @var string $title */
    protected $title = '';

    /** @var string|null $subTitle */
    protected $subTitle;

    /** @var string|null $imageUrl */
    protected $imageUrl;

    /** @var string|null $type */
    protected $type;

    /**
     * BasketItem constructor.
     *
     * @deprecated since 1.1.5.0 Please call constructor without parameters and use setter functions instead.
     *
     * @param string $title
     * @param float  $amountNet
     * @param float  $amountPerUnit
     * @param int    $quantity
     */
    public function __construct(
        string $title = '',
        float $amountNet = 0.0,
        float $amountPerUnit = 0.0,
        int $quantity = 1
    ) {
        $this->title                 = $title;
        $this->quantity              = $quantity;
        $this->setAmountNet($amountNet);
        $this->setAmountPerUnit($amountPerUnit);
    }

    /**
     * @return string|null
     */
    public function getBasketItemReferenceId(): ?string
    {
        return $this->basketItemReferenceId;
    }

    /**
     * @param string|null $basketItemReferenceId
     *
     * @return BasketItem
     */
    public function setBasketItemReferenceId(?string $basketItemReferenceId): BasketItem
    {
        $this->basketItemReferenceId = $basketItemReferenceId;
        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     *
     * @return BasketItem
     */
    public function setQuantity(int $quantity): BasketItem
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return float
     */
    public function getVat(): float
    {
        return $this->vat;
    }

    /**
     * @param float $vat
     *
     * @return BasketItem
     */
    public function setVat(float $vat): BasketItem
    {
        $this->vat = $vat;
        return $this;
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
     * @deprecated since 1.1.5.0  @see $setAmountDiscountPerUnitGross.
     *
     * @return BasketItem
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
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     *
     * @return BasketItem
     */
    public function setAmountGross(float $amountGross): BasketItem
    {
        $this->amountGross = $amountGross;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmountDiscountPerUnitGross(): float
    {
        return $this->amountDiscountPerUnitGross;
    }

    /**
     * @param float $amountDiscountPerUnitGross
     *
     * @return BasketItem
     */
    public function setAmountDiscountPerUnitGross(float $amountDiscountPerUnitGross): BasketItem
    {
        $this->amountDiscountPerUnitGross = $amountDiscountPerUnitGross;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmountPerUnitGross(): float
    {
        return $this->amountPerUnitGross;
    }

    /**
     * @param float $amountPerUnitGross
     *
     * @return BasketItem
     */
    public function setAmountPerUnitGross(float $amountPerUnitGross): BasketItem
    {
        $this->amountPerUnitGross = $amountPerUnitGross;
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
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     *
     * @return BasketItem
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
     * @deprecated since 1.1.5.0 @see setAmountPerUnitGross
     *
     * @return BasketItem
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
     * @deprecated since 1.1.5.0 Property is redundant and is no longer needed.
     *
     * @return BasketItem
     */
    public function setAmountNet(float $amountNet): BasketItem
    {
        $this->amountNet = $amountNet;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * @param string|null $unit
     *
     * @return BasketItem
     */
    public function setUnit(?string $unit): BasketItem
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return BasketItem
     */
    public function setTitle(string $title): BasketItem
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * @param string|null $imageUrl
     *
     * @return BasketItem
     */
    public function setImageUrl(?string $imageUrl): BasketItem
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    /**
     * @param string|null $subTitle
     *
     * @return BasketItem
     */
    public function setSubTitle(?string $subTitle): BasketItem
    {
        $this->subTitle = $subTitle;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * The type of the basket item.
     * Please refer to UnzerSDK\Constants\BasketItemTypes for available type constants.
     *
     * @param string|null $type
     *
     * @return BasketItem
     */
    public function setType(?string $type): BasketItem
    {
        $this->type = $type;
        return $this;
    }
}
