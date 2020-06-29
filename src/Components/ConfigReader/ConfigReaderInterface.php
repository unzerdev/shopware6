<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\ConfigReader;

use HeidelPayment6\Components\Struct\Configuration;

interface ConfigReaderInterface
{
    public function read(string $salesChannelId = '', bool $fallback = true): Configuration;
}
