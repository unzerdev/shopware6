<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\AddressHashGenerator;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;

class AddressHashGenerator implements AddressHashGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generateHash(CustomerAddressEntity $billingAddress, CustomerAddressEntity $shippingAddress): string
    {
        $data = [
            'billing' => [
                'countryId' => $billingAddress->getCountryId(),
                'firstName' => $billingAddress->getFirstName(),
                'lastName'  => $billingAddress->getLastName(),
                'zipCode'   => $billingAddress->getZipcode(),
                'city'      => $billingAddress->getCity(),
                'street'    => $billingAddress->getStreet(),
                'company'   => $billingAddress->getCompany(),
            ],
            'shipping' => [
                'countryId' => $shippingAddress->getCountryId(),
                'firstName' => $shippingAddress->getFirstName(),
                'lastName'  => $shippingAddress->getLastName(),
                'zipCode'   => $shippingAddress->getZipcode(),
                'city'      => $shippingAddress->getCity(),
                'street'    => $shippingAddress->getStreet(),
                'company'   => $shippingAddress->getCompany(),
            ],
        ];

        return md5(serialize($data));
    }
}
