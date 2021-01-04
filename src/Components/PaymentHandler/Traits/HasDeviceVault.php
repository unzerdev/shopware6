<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use stdClass;
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

        $exposedPaymentType = $this->paymentType->expose();

        if ($exposedPaymentType instanceof stdClass) {
            $encoded = json_encode($exposedPaymentType);

            if (!$encoded) {
                $exposedPaymentType = [];
            } else {
                $exposedPaymentType = json_decode($encoded, true);

                if (!is_array($exposedPaymentType) || empty($exposedPaymentType)) {
                    $exposedPaymentType = [];
                }
            }
        }

        $this->deviceRepository->create(
            $customer,
            $deviceType,
            $this->paymentType->getId(),
            array_merge($additionalParams, $exposedPaymentType),
            $context
        );
    }
}
