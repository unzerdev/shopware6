<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Struct;

class Configuration
{
    private $configuration;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return mixed|string
     */
    public function get(string $key, string $default = '')
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
