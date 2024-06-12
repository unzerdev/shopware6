<?php
/**
 * This trait adds geolocation to class.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

use UnzerSDK\Resources\EmbeddedResources\GeoLocation;

trait HasGeoLocation
{
    /** @var GeoLocation $geoLocation */
    private $geoLocation;

    /**
     * @return GeoLocation
     */
    public function getGeoLocation(): GeoLocation
    {
        if (empty($this->geoLocation)) {
            $this->geoLocation = new GeoLocation();
        }
        return $this->geoLocation;
    }

    /**
     * @param GeoLocation $geoLocation
     *
     * @return $this
     */
    public function setGeoLocation(GeoLocation $geoLocation): self
    {
        $this->geoLocation = $geoLocation;
        return $this;
    }
}
