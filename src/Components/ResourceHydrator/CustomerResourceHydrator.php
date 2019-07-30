<?php

declare(strict_types=1);

namespace HeidelPayment\Components\ResourceHydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\EmbeddedResources\Address;
use RuntimeException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomerResourceHydrator implements ResourceHydratorInterface
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

        $heidelCustomer = new Customer(
            $customer->getFirstName(),
            $customer->getLastName()
        );

        $heidelCustomer->setSalutation($customer->getSalutation() !== null ? $customer->getSalutation()->getSalutationKey() : null);
        $heidelCustomer->setEmail($customer->getEmail());
        $heidelCustomer->setCompany($customer->getCompany());
        $heidelCustomer->setBirthDate($customer->getBirthday() !== null ? $customer->getBirthday()->format('Y-m-d') : null);

        if ($shippingAddress) {
            $heidelCustomer->setShippingAddress((new Address())
                ->setCountry($shippingAddress->getCountry() !== null ? $shippingAddress->getCountry()->getIso() : null)
                ->setState($shippingAddress->getCountryState() !== null ? $shippingAddress->getCountryState()->getShortCode() : null)
                ->setZip($shippingAddress->getZipcode())
                ->setStreet($shippingAddress->getStreet())
                ->setCity($shippingAddress->getCity())
                ->setName($shippingAddress->getFirstName() . ' ' . $shippingAddress->getLastName())
            );
        }

        if ($billingAddress) {
            $heidelCustomer->setBillingAddress((new Address())
                ->setCountry($shippingAddress->getCountry() !== null ? $shippingAddress->getCountry()->getIso() : null)
                ->setState($shippingAddress->getCountryState() !== null ? $shippingAddress->getCountryState()->getShortCode() : null)
                ->setZip($shippingAddress->getZipcode())
                ->setStreet($shippingAddress->getStreet())
                ->setCity($shippingAddress->getCity())
                ->setName($shippingAddress->getFirstName() . ' ' . $shippingAddress->getLastName())
            );
        }

        return $heidelCustomer;
    }
}
