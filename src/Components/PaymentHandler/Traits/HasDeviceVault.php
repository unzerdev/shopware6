<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentHandler\Traits;

use HeidelPayment6\DataAbstractionLayer\Repository\PaymentDevice\HeidelpayPaymentDeviceRepositoryInterface;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;

/**
 * @property BasePaymentType $paymentType
 */
trait HasDeviceVault
{
    /** @var HeidelpayPaymentDeviceRepositoryInterface */
    protected $deviceRepository;

    protected function saveToDeviceVault(CustomerEntity $customer, string $deviceType, Context $context): void
    {
        if ($this->deviceRepository->exists($this->paymentType->getId(), $context)) {
            return;
        }

        $this->deviceRepository->create(
            $customer,
            $deviceType,
            $this->paymentType->getId(),
            $this->paymentType->expose(),
            $context
        );
    }
}
