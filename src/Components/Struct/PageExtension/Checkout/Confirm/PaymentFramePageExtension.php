<?php
declare(strict_types=1);

namespace HeidelPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;

class PaymentFramePageExtension extends Struct
{
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
