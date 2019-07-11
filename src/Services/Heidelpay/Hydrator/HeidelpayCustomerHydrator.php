<?php

namespace HeidelPayment\Services\Heidelpay\Hydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\EmbeddedResources\Address;
use RuntimeException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class HeidelpayCustomerHydrator implements HeidelpayHydratorInterface
{
    public function hydrateObject(
        SalesChannelContext $channelContext,
        ?AsyncPaymentTransactionStruct $transaction = null
    ): AbstractHeidelpayResource {
        $customer = $channelContext->getCustomer();

        if (!$customer) {
            throw new RuntimeException('Could not determine the customer');
        }

        $billingAddress  = $customer->getActiveBillingAddress();
        $shippingAddress = $customer->getActiveShippingAddress();

        $result = (new Customer(
            $customer->getFirstName(),
            $customer->getLastName()
        ))
            ->setSalutation($customer->getSalutation()->getSalutationKey())
            ->setEmail($customer->getEmail())
            ->setCompany($customer->getCompany())
            ->setBirthDate($customer->getBirthday() !== null ? $customer->getBirthday()->format('Y-m-d') : null);

        if ($shippingAddress) {
            $result->setShippingAddress((new Address())
                ->setCountry($shippingAddress->getCountry() !== null ? $shippingAddress->getCountry()->getIso() : null)
                ->setState($shippingAddress->getCountryState() !== null ? $shippingAddress->getCountryState()->getShortCode() : null)
                ->setZip($shippingAddress->getZipcode())
                ->setStreet($shippingAddress->getStreet())
                ->setCity($shippingAddress->getCity())
                ->setName($shippingAddress->getFirstName() . ' ' . $shippingAddress->getLastName())
            );
        }

        if ($billingAddress) {
            $result->setBillingAddress((new Address())
                ->setCountry($shippingAddress->getCountry() !== null ? $shippingAddress->getCountry()->getIso() : null)
                ->setState($shippingAddress->getCountryState() !== null ? $shippingAddress->getCountryState()->getShortCode() : null)
                ->setZip($shippingAddress->getZipcode())
                ->setStreet($shippingAddress->getStreet())
                ->setCity($shippingAddress->getCity())
                ->setName($shippingAddress->getFirstName() . ' ' . $shippingAddress->getLastName())
            );
        }

        return $result;
    }
}
