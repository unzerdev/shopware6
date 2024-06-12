<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This dummy class is used to verify certain behaviour of the AbstractUnzerResource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources;

use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Customer;

class DummyUnzerResource extends AbstractUnzerResource
{
    /** @var Customer $customer */
    private $customer;

    /**
     * DummyUnzerResource constructor.
     *
     * @param Customer $customer
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * {@inheritDoc}
     */
    public function getLinkedResources(): array
    {
        return ['customer' => $this->customer];
    }
}
