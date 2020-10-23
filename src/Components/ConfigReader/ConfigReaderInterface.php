<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ConfigReader;

use UnzerPayment6\Components\Struct\Configuration;

interface ConfigReaderInterface
{
    public function read(string $salesChannelId = '', bool $fallback = true): Configuration;
}
