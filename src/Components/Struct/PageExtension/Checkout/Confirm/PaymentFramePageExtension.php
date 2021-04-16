<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;

class PaymentFramePageExtension extends Struct
{
    public const EXTENSION_NAME = 'unzerPaymentFrame';

    /** @var string */
    private $paymentFrame;

    public function getPaymentFrame(): string
    {
        return $this->paymentFrame;
    }

    public function setPaymentFrame(string $paymentFrame): self
    {
        $this->paymentFrame = $paymentFrame;

        return $this;
    }
}
