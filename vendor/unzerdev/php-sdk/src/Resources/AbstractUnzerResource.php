<?php
/**
 * This is the base class for all resource types managed by the api.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Resources;

use DateTime;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Constants\AdditionalAttributes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Unzer;
use UnzerSDK\Interfaces\UnzerParentInterface;
use UnzerSDK\Services\ResourceNameService;
use UnzerSDK\Services\ResourceService;
use UnzerSDK\Services\ValueService;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use stdClass;

use function count;
use function is_array;
use function is_callable;
use function is_float;
use function is_object;

/**
 * This is a class representing a resource on Unzer payment API.
 *
 * @link  https://docs.unzer.com/
 *
 */
abstract class AbstractUnzerResource implements UnzerParentInterface
{
    /** @var string $id */
    protected $id;

    /** @var UnzerParentInterface */
    private $parentResource;

    /** @var DateTime */
    private $fetchedAt;

    /** @var array $specialParams */
    private $specialParams = [];

    /** @var array $additionalAttributes */
    protected $additionalAttributes = [];

    /**
     * Returns the API name of the resource.
     *
     * @return string
     */
    public static function getResourceName(): string
    {
        return ResourceNameService::getClassShortNameKebapCase(static::class);
    }

    /**
     * Returns the id of this resource.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * This setter must be public to enable fetching a resource by setting the id and then call fetch.
     *
     * @param string|null $resourceId
     *
     * @return $this
     */
    public function setId(?string $resourceId): self
    {
        $this->id = $resourceId;
        return $this;
    }

    /**
     * @param UnzerParentInterface|null $parentResource
     *
     * @return $this
     */
    public function setParentResource(?UnzerParentInterface $parentResource): self
    {
        $this->parentResource = $parentResource;
        return $this;
    }

    /**
     * @return UnzerParentInterface
     *
     * @throws RuntimeException
     */
    public function getParentResource(): UnzerParentInterface
    {
        if (!$this->parentResource instanceof UnzerParentInterface) {
            throw new RuntimeException('Parent resource reference is not set!');
        }
        return $this->parentResource;
    }

    /**
     * @return DateTime|null
     */
    public function getFetchedAt(): ?DateTime
    {
        return $this->fetchedAt;
    }

    /**
     * @param DateTime $fetchedAt
     *
     * @return self
     */
    public function setFetchedAt(DateTime $fetchedAt): self
    {
        $this->fetchedAt = $fetchedAt;
        return $this;
    }

    /**
     * Returns an array of additional params which can be added to the resource request.
     *
     * @return array
     */
    public function getSpecialParams(): array
    {
        return $this->specialParams;
    }

    /**
     * Sets the array of additional params which are to be added to the resource request.
     *
     * @param array $specialParams
     *
     * @return self
     */
    public function setSpecialParams(array $specialParams): self
    {
        $this->specialParams = $specialParams;
        return $this;
    }

    /**
     * Returns the API version for this resource.
     *
     * @return string
     */
    public function getApiVersion(): string
    {
        return Unzer::API_VERSION;
    }

    /**
     * @param array $additionalAttributes
     *
     * @return AbstractUnzerResource
     */
    protected function setAdditionalAttributes(array $additionalAttributes): AbstractUnzerResource
    {
        $this->additionalAttributes = $additionalAttributes;
        return $this;
    }

    /**
     * Adds the given value to the additionalAttributes array if it is not set yet.
     * Overwrites the given value if it already exists.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return AbstractUnzerResource
     *
     * @see AdditionalAttributes
     *
     */
    public function setAdditionalAttribute(string $attribute, $value): AbstractUnzerResource
    {
        $this->additionalAttributes[$attribute] = $value;
        return $this;
    }

    /**
     * Returns the value of the given attribute or null if it is not set.
     *
     * @param string $attribute
     *
     * @see AdditionalAttributes
     *
     * @return mixed
     */
    public function getAdditionalAttribute(string $attribute)
    {
        return $this->additionalAttributes[$attribute] ?? null;
    }

    /**
     * @return array
     */
    public function getAdditionalAttributes()
    {
        return $this->additionalAttributes;
    }

    /**
     * {@inheritDoc}
     */
    public function getUnzerObject(): Unzer
    {
        return $this->getParentResource()->getUnzerObject();
    }

    /**
     * Fetches the parent URI and combines it with the uri of the current resource.
     * If appendId is set the id of the current resource will be appended if it is set.
     * The flag appendId is always set for getUri of the parent resource.
     *
     * {@inheritDoc}
     *
     * @throws RuntimeException
     */
    public function getUri(bool $appendId = true, string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        $uri = [rtrim($this->getParentResource()->getUri(), '/'), $this->getResourcePath($httpMethod)];
        if ($appendId) {
            if ($this->getId() !== null) {
                $uri[] = $this->getId();
            } elseif ($this->getExternalId() !== null) {
                $uri[] = $this->getExternalId();
            }
        }

        return implode('/', $uri);
    }

    /**
     * This method updates the properties of the resource.
     *
     * @param          $object
     * @param stdClass $response
     */
    private static function updateValues($object, stdClass $response): void
    {
        foreach ($response as $key => $value) {
            // set empty string to null (workaround)
            $newValue = $value === '' ? null : $value;

            // handle nested object
            if (is_object($value)) {
                $getter = 'get' . ucfirst($key);
                if (is_callable([$object, $getter])) {
                    self::updateValues($object->$getter(), $newValue);
                } elseif ($key === 'processing') {
                    self::updateValues($object, $newValue);
                }
                continue;
            }

            // handle nested array
            if (is_array($value)) {
                $firstItem = reset($value);
                if (is_object($firstItem)) {
                    // Handled by the owning object since we do not know the type of the items here.
                    continue;
                }
            }

            // handle basic types
            self::setItemProperty($object, $key, $newValue);
        }
    }

    /**
     * @return ResourceService
     *
     * @throws RuntimeException
     */
    private function getResourceService(): ResourceService
    {
        return $this->getUnzerObject()->getResourceService();
    }

    /**
     * Fetches the Resource if it has not been fetched yet and the id is set.
     *
     * @param AbstractUnzerResource $resource
     *
     * @return AbstractUnzerResource
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    protected function getResource(AbstractUnzerResource $resource): AbstractUnzerResource
    {
        return $this->getResourceService()->getResource($resource);
    }

    /**
     * Fetch the given resource object.
     *
     * @param AbstractUnzerResource $resource
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    protected function fetchResource(AbstractUnzerResource $resource): void
    {
        $this->getResourceService()->fetchResource($resource);
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return false|string data which can be serialized by <b>json_encode</b>,
     *                      which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return json_encode($this->expose(), JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * Creates an array containing all properties to be exposed to the Unzer api as resource parameters.
     *
     * @return array|stdClass
     */
    public function expose()
    {
        $properties = $this->exposeProperties();
        $resources  = $this->exposeLinkedResources();

        if (count($resources) > 0) {
            ksort($resources);
            $properties['resources'] = $resources;
        }

        // Add special params if any
        foreach ($this->getSpecialParams() as $attributeName => $specialParam) {
            $properties[$attributeName] = $specialParam;
        }
        //---------------------

        ksort($properties);
        return count($properties) > 0 ? $properties : new stdClass();
    }

    /**
     * @param array $value
     *
     * @return array
     */
    private function exposeAdditionalAttributes(array $value): array
    {
        foreach ($value as $attributeName => $attributeValue) {
            $attributeValue        = ValueService::limitFloats($attributeValue);
            $value[$attributeName] = $attributeValue;
            $this->setAdditionalAttribute($attributeName, $attributeValue);
        }
        return $value;
    }

    /**
     * Returns true if the given property should be skipped.
     *
     * @param string $property
     * @param        $value
     *
     * @return bool
     */
    private static function propertyShouldBeSkipped(string $property, $value): bool
    {
        $skipProperty = false;

        try {
            $reflection = new ReflectionProperty(static::class, $property);
            if ($value === null ||                          // do not send properties that are set to null
                ($property === 'id' && empty($value)) ||    // do not send id property if it is empty
                !$reflection->isProtected()) {              // only send protected properties
                $skipProperty = true;
            }
        } /** @noinspection BadExceptionsProcessingInspection */
        catch (ReflectionException $e) {
            $skipProperty = true;
        }

        return $skipProperty;
    }

    /**
     * Can not be moved to service since setters and getters are most likely private.
     *
     * @param $item
     * @param $key
     * @param $value
     */
    private static function setItemProperty($item, $key, $value): void
    {
        $setter = 'set' . ucfirst($key);
        if (!is_callable([$item, $setter])) {
            $setter = 'add' . ucfirst($key);
        }
        if (is_callable([$item, $setter])) {
            $item->$setter($value);
        }
    }

    /**
     * Return the resources which should be referenced by ID within the resource section of the resource data.
     * Override this to define the linked resources.
     *
     * @return array
     */
    public function getLinkedResources(): array
    {
        return [];
    }

    /**
     * This returns the path of this resource within the parent resource.
     * Override this if the path does not match the class name.
     *
     * @param string $httpMethod
     *
     * @return string
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return self::getResourceName($httpMethod);
    }

    /**
     * This method is called to handle the response from a crud command.
     * Override it to handle the data correctly.
     *
     * @param stdClass $response
     * @param string   $method
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        self::updateValues($this, $response);

        // Todo: Workaround to be removed when API sends TraceID in processing-group
        if (
            isset($response->resources->traceId) &&
            is_callable([$this, 'setTraceId']) &&
            is_callable([$this, 'getTraceId']) &&
            $this->getTraceId() === null
        ) {
            $this->setTraceId($response->resources->traceId);
        }
        // Todo: Workaround end
    }

    /**
     * Returns the externalId of a resource if the resource supports to be loaded by it.
     * Override this in the resource class.
     *
     * @return string|null
     */
    public function getExternalId(): ?string
    {
        return null;
    }

    /**
     * Exposes properties
     *
     * @return array
     */
    private function exposeProperties(): array
    {
        $properties = get_object_vars($this);
        foreach ($properties as $property => $value) {
            if (self::propertyShouldBeSkipped($property, $value)) {
                unset($properties[$property]);
                continue;
            }

            // expose child objects if possible
            if ($value instanceof self) {
                $value = $value->expose();
            }

            // reduce floats to 4 decimal places and update the property in object
            if (is_float($value)) {
                $value = ValueService::limitFloats($value);
                self::setItemProperty($this, $property, $value);
            }

            // handle additional values
            if ($property === 'additionalAttributes') {
                if (!is_array($value) || empty($value)) {
                    unset($properties[$property]);
                    continue;
                }
                $value = $this->exposeAdditionalAttributes($value);
            }

            // handle additionalTransactionData values
            if ($property === 'additionalTransactionData') {
                foreach ($value as $key => $data) {
                    if ($data instanceof self) {
                        $value->$key = $data->expose();
                    }
                }
            }

            $properties[$property] = $value;
        }

        return $properties;
    }

    /**
     * Expose linked resources if any.
     *
     * @return array
     */
    private function exposeLinkedResources(): array
    {
        $exposedResources = [];
        /**
         * @var string                $attributeName
         * @var AbstractUnzerResource $linkedResource
         */
        foreach ($this->getLinkedResources() as $attributeName => $linkedResource) {
            $resourceId = $linkedResource ? $linkedResource->getId() : null;
            if ($resourceId !== null) {
                $exposedResources[$attributeName . 'Id'] = $resourceId;
            }
        }
        return $exposedResources;
    }
}
