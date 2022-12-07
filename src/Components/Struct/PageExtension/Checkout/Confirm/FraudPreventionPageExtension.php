<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;

class FraudPreventionPageExtension extends Struct
{
    public const EXTENSION_NAME = 'unzerFraudPrevention';

    /** @var string */
    private $fraudPreventionSessionId = '';

    public function getFraudPreventionSessionId(): string
    {
        return $this->fraudPreventionSessionId;
    }

    public function setFraudPreventionSessionId(string $fraudPreventionSessionId): void
    {
        $this->fraudPreventionSessionId = $fraudPreventionSessionId;
    }
}
