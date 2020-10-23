<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;

/**
 * @property BasePaymentType $paymentType
 */
trait HasDeviceVault
{
    /** @var UnzerPaymentDeviceRepositoryInterface */
    protected $deviceRepository;

    protected function saveToDeviceVault(CustomerEntity $customer, string $deviceType, Context $context, array $additionalParams = []): void
    {
        if ($this->deviceRepository->exists($this->paymentType->getId(), $context)) {
            return;
        }

        $this->deviceRepository->create(
            $customer,
            $deviceType,
            $this->paymentType->getId(),
            array_merge($additionalParams, $this->paymentType->expose()),
            $context
        );
    }
}
