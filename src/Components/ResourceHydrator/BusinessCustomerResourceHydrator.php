<?php

declare(strict_types=1);

namespace HeidelPayment\Components\ResourceHydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\CustomerFactory;
use heidelpayPHP\Resources\EmbeddedResources\Address;
use RuntimeException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class BusinessCustomerResourceHydrator implements ResourceHydratorInterface
{
    public function hydrateObject(
        SalesChannelContext $channelContext,
        ?AsyncPaymentTransactionStruct $transaction = null
    ): AbstractHeidelpayResource {
        $customer = $channelContext->getCustomer();

        if (!$customer) {
            throw new RuntimeException('Could not determine the customer');
        }

        if ($customer->getActiveBillingAddress() === null) {
            throw new RuntimeException('Could not determine the customer`s billing address');
        }

        $birthday       = $customer->getBirthday() ? $customer->getBirthday()->format('Y-m-d') : '';
        $billingAddress = $customer->getActiveBillingAddress();

        return CustomerFactory::createNotRegisteredB2bCustomer(
            $customer->getFirstName(),
            $customer->getLastName(),
            $birthday,
            $this->getHeidelpayAddress($billingAddress),
            $customer->getEmail(),
            $customer->getCompany() ?? $billingAddress->getCompany()
        );
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
