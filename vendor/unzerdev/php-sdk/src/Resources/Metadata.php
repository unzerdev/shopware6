<?php

namespace UnzerSDK\Resources;

use UnzerSDK\Adapter\HttpAdapterInterface;
use stdClass;

use function count;
use function in_array;
use function is_callable;

/**
 * This represents the metadata resource.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Metadata extends AbstractUnzerResource
{
    private $metadata = [];

    protected $shopType;
    protected $shopVersion;

    /**
     * @return string|null
     */
    public function getShopType(): ?string
    {
        return $this->shopType;
    }

    /**
     * @param string $shopType
     *
     * @return Metadata
     */
    public function setShopType(string $shopType): Metadata
    {
        $this->shopType = $shopType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShopVersion(): ?string
    {
        return $this->shopVersion;
    }

    /**
     * @param string $shopVersion
     *
     * @return Metadata
     */
    public function setShopVersion(string $shopVersion): Metadata
    {
        $this->shopVersion = $shopVersion;
        return $this;
    }

    /**
     * Magic setter
     *
     * @param string $name
     * @param string $value
     *
     * @return Metadata
     */
    public function addMetadata(string $name, string $value): Metadata
    {
        if (!in_array(strtolower($name), ['sdkversion', 'sdktype', 'shoptype', 'shopversion'])) {
            $this->metadata[$name] = $value;
        }

        return $this;
    }

    /**
     * Getter function for custom criterion fields.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getMetadata(string $name)
    {
        return $this->metadata[$name] ?? null;
    }

    /**
     * Add the dynamically set meta data.
     * {@inheritDoc}
     */
    public function expose()
    {
        $array_merge = array_merge((array)parent::expose(), $this->metadata);
        return count($array_merge) > 0 ? $array_merge : new stdClass();
    }

    /**
     * Add custom properties (i.e. properties without setter) to the metadata array.
     * {@inheritDoc}
     */
    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        parent::handleResponse($response, $method);

        foreach ($response as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (!is_callable([$this, $setter])) {
                $this->addMetadata($key, $value);
            }
        }
    }
}
