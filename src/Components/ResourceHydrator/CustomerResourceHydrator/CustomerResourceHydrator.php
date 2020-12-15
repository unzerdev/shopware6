<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\CustomerFactory;
use heidelpayPHP\Resources\EmbeddedResources\Address;
use RuntimeException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomerResourceHydrator implements CustomerResourceHydratorInterface
{
    public function hydrateObject(SalesChannelContext $channelContext): AbstractHeidelpayResource
    {
        $customer = $channelContext->getCustomer();

        if (!$customer) {
            throw new RuntimeException('Could not determine the customer');
        }

        $billingAddress  = $customer->getActiveBillingAddress();
        $shippingAddress = $customer->getActiveShippingAddress();

        if (!$billingAddress || !$shippingAddress) {
            throw new RuntimeException(sprintf('Could not determine the address for customer with number %s', $customer->getCustomerNumber()));
        }

        if (empty($billingAddress->getCompany())) {
            $unzerCustomer = CustomerFactory::createCustomer(
                $customer->getFirstName(),
                $customer->getLastName()
            );
        } else {
            $unzerCustomer = CustomerFactory::createNotRegisteredB2bCustomer(
                $customer->getFirstName(),
                $customer->getLastName(),
                '',
                $this->getUnzerAddress($billingAddress),
                $customer->getEmail(),
                !empty($billingAddress->getCompany()) ? $billingAddress->getCompany() : ''
            );
        }

        $unzerCustomer->setShippingAddress($this->getUnzerAddress($shippingAddress));
        $unzerCustomer->setBillingAddress($this->getUnzerAddress($billingAddress));
        $unzerCustomer->setCustomerId($customer->getCustomerNumber());

        return $this->addAdditionalDataToCustomer($unzerCustomer, $customer, $billingAddress);
    }

    public function hydrateExistingCustomer(AbstractHeidelpayResource $unzerCustomer, SalesChannelContext $salesChannelContext): AbstractHeidelpayResource
    {
        if (!$unzerCustomer instanceof Customer) {
            return $unzerCustomer;
        }

        $customer = $salesChannelContext->getCustomer();

        if (!$customer) {
            throw new RuntimeException('Could not determine the customer');
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if (!$billingAddress) {
            throw new RuntimeException(sprintf('Could not determine the address for customer with number %s', $customer->getCustomerNumber()));
        }

        return $this->addAdditionalDataToCustomer($unzerCustomer, $customer, $billingAddress);
    }

    protected function getUnzerAddress(CustomerAddressEntity $shopwareAddress): Address
    {
        $address = new Address();
        $address->setCountry($shopwareAddress->getCountry() !== null ? $shopwareAddress->getCountry()->getIso() : null);
        $address->setState($shopwareAddress->getCountryState() !== null ? $shopwareAddress->getCountryState()->getShortCode() : null);
        $address->setZip($shopwareAddress->getZipcode());
        $address->setStreet($shopwareAddress->getStreet());
        $address->setCity($shopwareAddress->getCity());
        $address->setName(sprintf('%s %s', $shopwareAddress->getFirstName(), $shopwareAddress->getLastName()));

        return $address;
    }

    protected function addAdditionalDataToCustomer(Customer $unzerCustomer, CustomerEntity $customer, CustomerAddressEntity $billingAddress): Customer
    {
        if (empty($unzerCustomer->getFirstname())) {
            $unzerCustomer->setFirstname($customer->getFirstName());
        }

        if (empty($unzerCustomer->getLastname())) {
            $unzerCustomer->setLastname($customer->getLastName());
        }

        if (empty($unzerCustomer->getEmail())) {
            $unzerCustomer->setEmail($customer->getEmail());
        }

        if (empty($unzerCustomer->getSalutation())) {
            $unzerCustomer->setSalutation($customer->getSalutation() !== null ? $customer->getSalutation()->getSalutationKey() : null);
        }

        if (empty($unzerCustomer->getBirthDate())) {
            $unzerCustomer->setBirthDate($customer->getBirthday() !== null ? $customer->getBirthday()->format('Y-m-d') : null);
        }

        if (empty($unzerCustomer->getCompany()) && !empty($billingAddress->getCompany())) {
            $unzerCustomer->setCompany($billingAddress->getCompany());
        }

        return $unzerCustomer;
    }
}
