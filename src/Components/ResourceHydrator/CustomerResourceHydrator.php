<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\ResourceHydrator;

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

        $heidelCustomer = new Customer(
            $customer->getFirstName(),
            $customer->getLastName()
        );

        $heidelCustomer->setSalutation($customer->getSalutation() !== null ? $customer->getSalutation()->getSalutationKey() : null);
        $heidelCustomer->setEmail($customer->getEmail());
        $heidelCustomer->setCompany($customer->getCompany());
        $heidelCustomer->setBirthDate($customer->getBirthday() !== null ? $customer->getBirthday()->format('Y-m-d') : null);

        if ($shippingAddress) {
            $heidelCustomer->setShippingAddress($this->getHeidelpayAddress($shippingAddress));
        }

        if ($billingAddress) {
            $heidelCustomer->setBillingAddress($this->getHeidelpayAddress($billingAddress));
        }

        return $heidelCustomer;
    }

    private function getHeidelpayAddress(CustomerAddressEntity $shopwareAddress): Address
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
