<?php

namespace UnzerSDK\Resources\EmbeddedResources\Paypage;

use UnzerSDK\Resources\AbstractUnzerResource;

class Style extends AbstractUnzerResource
{
    protected ?bool $hideBasket = null;
    protected ?bool $hideUnzerLogo = null;
    protected ?bool $shadows = null;
    protected ?string $backgroundColor = null;
    protected ?string $backgroundImage = null;
    protected ?string $brandColor = null;
    protected ?string $cornerRadius = null;
    protected ?string $favicon = null;
    protected ?string $font = null;
    protected ?string $footerColor = null;
    protected ?string $headerColor = null;
    protected ?string $logoImage = null;
    protected ?string $linkColor = null;
    protected ?string $textColor = null;

    public function getHideBasket(): ?bool
    {
        return $this->hideBasket;
    }

    public function setHideBasket(?bool $hideBasket): Style
    {
        $this->hideBasket = $hideBasket;
        return $this;
    }

    public function getFavicon(): ?string
    {
        return $this->favicon;
    }

    public function setFavicon(?string $favicon): Style
    {
        $this->favicon = $favicon;
        return $this;
    }

    public function getLogoImage(): ?string
    {
        return $this->logoImage;
    }

    public function setLogoImage(?string $logoImage): Style
    {
        $this->logoImage = $logoImage;
        return $this;
    }

    public function getFont(): ?string
    {
        return $this->font;
    }

    public function setFont(?string $font): Style
    {
        $this->font = $font;
        return $this;
    }

    public function getBrandColor(): ?string
    {
        return $this->brandColor;
    }

    public function setBrandColor(?string $brandColor): Style
    {
        $this->brandColor = $brandColor;
        return $this;
    }

    public function getTextColor(): ?string
    {
        return $this->textColor;
    }

    public function setTextColor(?string $textColor): Style
    {
        $this->textColor = $textColor;
        return $this;
    }

    public function getLinkColor(): ?string
    {
        return $this->linkColor;
    }

    public function setLinkColor(?string $linkColor): Style
    {
        $this->linkColor = $linkColor;
        return $this;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor): Style
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    public function getCornerRadius(): ?string
    {
        return $this->cornerRadius;
    }

    public function setCornerRadius(?string $cornerRadius): Style
    {
        $this->cornerRadius = $cornerRadius;
        return $this;
    }

    public function getShadows(): ?bool
    {
        return $this->shadows;
    }

    public function setShadows(?bool $shadows): Style
    {
        $this->shadows = $shadows;
        return $this;
    }

    public function getHideUnzerLogo(): ?bool
    {
        return $this->hideUnzerLogo;
    }

    public function setHideUnzerLogo(?bool $hideUnzerLogo): Style
    {
        $this->hideUnzerLogo = $hideUnzerLogo;
        return $this;
    }

    public function getBackgroundImage(): ?string
    {
        return $this->backgroundImage;
    }

    public function setBackgroundImage(?string $backgroundImage): Style
    {
        $this->backgroundImage = $backgroundImage;
        return $this;
    }

    public function getFooterColor(): ?string
    {
        return $this->footerColor;
    }

    public function setFooterColor(?string $footerColor): Style
    {
        $this->footerColor = $footerColor;
        return $this;
    }

    public function getHeaderColor(): ?string
    {
        return $this->headerColor;
    }

    public function setHeaderColor(?string $headerColor): Style
    {
        $this->headerColor = $headerColor;
        return $this;
    }
}