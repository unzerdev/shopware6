<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\AddressHashGenerator;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;

interface AddressHashGeneratorInterface
{
    public function generateHash(CustomerAddressEntity|OrderAddressEntity $billingAddress, CustomerAddressEntity|OrderAddressEntity $shippingAddress): string;
}
