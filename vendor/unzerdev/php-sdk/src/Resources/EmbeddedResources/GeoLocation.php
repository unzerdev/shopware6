<?php

namespace UnzerSDK\Resources\EmbeddedResources;

use UnzerSDK\Resources\AbstractUnzerResource;

/**
 * Represents the geo location of an entity.
 *
 * @link  https://docs.unzer.com/
 *
 */
class GeoLocation extends AbstractUnzerResource
{
    /** @var string|null $clientIp */
    private $clientIp;

    /** @var string|null $countryCode */
    private $countryCode;

    /**
     * @return string|null
     */
    public function getClientIp(): ?string
    {
        return $this->clientIp;
    }

    /**
     * @param string|null $clientIp
     *
     * @return GeoLocation
     */
    protected function setClientIp(?string $clientIp): GeoLocation
    {
        $this->clientIp = $clientIp;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    /**
     * @param string|null $countryCode
     *
     * @return GeoLocation
     */
    protected function setCountryCode(?string $countryCode): GeoLocation
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    /**
     * @param string|null $countryCode
     *
     * @return GeoLocation
     */
    protected function setCountryIsoA2(?string $countryCode): GeoLocation
    {
        return $this->setCountryCode($countryCode);
    }
}
