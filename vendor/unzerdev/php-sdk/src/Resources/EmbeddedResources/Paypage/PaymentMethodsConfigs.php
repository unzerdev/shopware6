<?php

namespace UnzerSDK\Resources\EmbeddedResources\Paypage;

use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Services\ResourceNameService;

class PaymentMethodsConfigs extends AbstractUnzerResource
{
    const PAYPAGE_TYPE_NAME_MAPPING = [
        'card' => 'cards',
        'eps' => 'eps',
        'payu' => 'payu',
        'postfinanceefinance' => 'pfefinance',
        'postfinancecard' => 'pfcard'
    ];

    protected ?PaymentMethodConfig $default = null;

    /** @var PaymentMethodConfig[] */
    protected ?array $methodConfigs = null;

    /**
     * Takes Class name of a payment type and maps it to a method name used in Paypage service if needed.
     *
     * @param BasePaymentType $paymentType Full or short class name of a payment type.
     * @param PaymentMethodConfig $methodConfig Configuration object for the payment method.
     * @return $this
     */
    public function addMethodConfig(string $paymentType, PaymentMethodConfig $methodConfig): PaymentMethodsConfigs
    {
        $classShortName = ResourceNameService::getClassShortName($paymentType);
        $mappedName = $this->mapClassToMethodName($classShortName);

        $this->methodConfigs[lcfirst($mappedName)] = $methodConfig;
        return $this;
    }

    public function expose()
    {
        $exposeArray = parent::expose();
        $paymentMethodConfigs = $this->getMethodConfigs();

        if (empty($paymentMethodConfigs)) {
            return $exposeArray;
        }

        foreach ($paymentMethodConfigs as $type => $methodConfig) {
            $exposeArray[$type] = $methodConfig->expose();
        }
        unset($exposeArray['methodConfigs']);

        return $exposeArray;
    }


    public function getDefault(): ?PaymentMethodConfig
    {
        return $this->default;
    }

    public function setDefault(?PaymentMethodConfig $default): PaymentMethodsConfigs
    {
        $this->default = $default;
        return $this;
    }

    public function getMethodConfigs(): ?array
    {
        return $this->methodConfigs;
    }

    public function setMethodConfigs(?array $methodConfigs): PaymentMethodsConfigs
    {
        $this->methodConfigs = $methodConfigs;
        return $this;
    }

    /**
     * @param array $replace
     * @param array $with
     * @param string $classShortName
     * @return array|string|string[]|null
     */
    public function mapClassToMethodName(string $classShortName)
    {
        $typeMapping = self::PAYPAGE_TYPE_NAME_MAPPING;

        // keys are in lower case.
        $searchKey = strtolower($classShortName);
        if (isset($typeMapping[$searchKey])) {
            return $typeMapping[$searchKey];
        }

        return $classShortName;
    }
}