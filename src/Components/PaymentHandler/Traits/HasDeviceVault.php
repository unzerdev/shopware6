<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use stdClass;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

/**
 * @property BasePaymentType $paymentType
 */
trait HasDeviceVault
{
    protected readonly EntityRepository $customerRepository;

    protected UnzerPaymentDeviceRepositoryInterface $deviceRepository;


    protected function tryToSaveToDeviceVault(string $customerId, string $deviceType, Context $context, array $additionalParams = []): void
    {

        try {
            $criteria = new Criteria([$customerId]);
            $criteria->addAssociations([
                'defaultBillingAddress',
                'defaultShippingAddress',
            ]);
            //customer chose to save credit card
            $customer = $this->customerRepository->search(
                $criteria,
                $context
            )->first();

            $this->saveToDeviceVault(
                $customer,
                $deviceType,
                $context,
                $additionalParams
            );
        } catch (\Throwable $e) {
            $this->logger->warning('Could not save to device vault: ' . $e->getMessage());
        }
    }

    private function saveToDeviceVault(?CustomerEntity $customer, string $deviceType, Context $context, array $additionalParams = []): void
    {
        if (!$this->canSaveToDeviceVault($customer)) {
            return;
        }
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

                if (!\is_array($exposedPaymentType) || empty($exposedPaymentType)) {
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

    private function canSaveToDeviceVault(?CustomerEntity $customer): bool
    {
        return $customer !== null && $customer->getGuest() === false;
    }
}
