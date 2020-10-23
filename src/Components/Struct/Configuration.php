<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct;

class Configuration
{
    /** @var array */
    private $configuration;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    public function get(string $key, $default = '')
    {
        if (!array_key_exists($key, $this->configuration)) {
            return $default;
        }

        if (empty($this->configuration[$key])) {
            return $default;
        }

        return $this->configuration[$key];
    }
}
