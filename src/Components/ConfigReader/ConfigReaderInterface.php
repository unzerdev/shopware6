<?php

declare(strict_types=1);

namespace HeidelPayment\Components\ConfigReader;

use HeidelPayment\Components\Struct\Configuration;

interface ConfigReaderInterface
{
    public function read(string $salesChannelId = '', bool $fallback = true): Configuration;
}
