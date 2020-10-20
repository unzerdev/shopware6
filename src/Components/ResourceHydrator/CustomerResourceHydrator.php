<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\EmbeddedResources\Address;
use RuntimeException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomerResourceHydrator implements ResourceHydratorInterface
{
    public function hydrateObject(
        SalesChannelContext $channelContext,
        $transaction = null
    ): AbstractHeidelpayResource {
        $customer = $channelContext->getCustomer();

        if (!$customer) {
            throw new RuntimeException('Could not determine the customer');
        }

        $billingAddress  = $customer->getActiveBillingAddress();
        $shippingAddress = $customer->getActiveShippingAddress();

        $unzerCustomer = new Customer(
            $customer->getFirstName(),
            $customer->getLastName()
        );

        $unzerCustomer->setSalutation($customer->getSalutation() !== null ? $customer->getSalutation()->getSalutationKey() : null);
        $unzerCustomer->setEmail($customer->getEmail());
        $unzerCustomer->setCompany($customer->getCompany());
        $unzerCustomer->setBirthDate($customer->getBirthday() !== null ? $customer->getBirthday()->format('Y-m-d') : null);

        if ($shippingAddress) {
            $unzerCustomer->setShippingAddress($this->getUnzerAddress($shippingAddress));
        }

        if ($billingAddress) {
            $unzerCustomer->setBillingAddress($this->getUnzerAddress($billingAddress));
        }

        return $unzerCustomer;
    }

    private function getUnzerAddress(CustomerAddressEntity $shopwareAddress): Address
    {
        $address = new Address();
        $address->setCountry($shopwareAddress->getCountry() !== null ? $shopwareAddress->getCountry()->getIso() : null);
        $address->setState($shopwareAddress->getCountryState() !== null ? $shopwareAddress->getCountryState()->getShortCode() : null);
        $address->setZip($shopwareAddress->getZipcode());
        $address->setStreet($shopwareAddress->getStreet());
        $address->setCity($shopwareAddress->getCity());
        $address->setName($shopwareAddress->getFirstName() . ' ' . $shopwareAddress->getLastName());

        return $address;
    }
}
