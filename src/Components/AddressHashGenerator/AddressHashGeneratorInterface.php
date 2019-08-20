<?php

declare(strict_types=1);

namespace HeidelPayment\Components\AddressHashGenerator;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;

interface AddressHashGeneratorInterface
{
    public function generateHash(CustomerAddressEntity $billingAddress, CustomerAddressEntity $shippingAddress): string;
}
