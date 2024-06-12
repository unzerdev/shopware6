<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines a dummy resource used for unit tests.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\AbstractUnzerResource;

class DummyResource extends AbstractUnzerResource
{
    /** @var float $testFloat */
    protected $testFloat = 0.0;

    //<editor-fold desc="Setters / Getters">

    /**
     * @return float
     */
    public function getTestFloat(): float
    {
        return $this->testFloat;
    }

    /**
     * @param float $testFloat
     *
     * @return DummyResource
     */
    public function setTestFloat(float $testFloat): DummyResource
    {
        $this->testFloat = $testFloat;
        return $this;
    }

    //</editor-fold>

    //<editor-fold desc="Overridable methods">
    public function jsonSerialize()
    {
        return '{"dummyResource": "JsonSerialized"}';
    }

    public function getUri(bool $appendId = true, string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return '/my/uri' . ($appendId ? '/123' : '');
    }

    //</editor-fold>
}
