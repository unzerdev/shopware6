<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator;

use RuntimeException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\Address;

class CustomerResourceHydrator implements CustomerResourceHydratorInterface
{
    private const B2B_CUSTOMERS_ALLOWED = [
        PaymentInstaller::PAYMENT_ID_INVOICE_SECURED,
        PaymentInstaller::PAYMENT_ID_DIRECT_DEBIT_SECURED,
    ];

    /** @var RequestStack */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function hydrateObject(
        string $paymentMethodId,
        SalesChannelContext $channelContext
    ): AbstractUnzerResource {
        $customer = $channelContext->getCustomer();

        if (!$customer) {
            throw new RuntimeException('Could not determine the customer');
        }

        $billingAddress  = $customer->getActiveBillingAddress();
        $shippingAddress = $customer->getActiveShippingAddress();

        if (!$billingAddress || !$shippingAddress) {
            throw new RuntimeException(sprintf('Could not determine the address for customer with number %s', $customer->getCustomerNumber()));
        }

        if (empty($billingAddress->getCompany()) || !in_array($paymentMethodId, self::B2B_CUSTOMERS_ALLOWED, true)) {
            $unzerCustomer = CustomerFactory::createCustomer(
                $customer->getFirstName(),
                $customer->getLastName()
            );
        } else {
            $unzerCustomer = CustomerFactory::createNotRegisteredB2bCustomer(
                $customer->getFirstName(),
                $customer->getLastName(),
                $this->getBirthDate($customer),
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

    public function hydrateExistingCustomer(
        AbstractUnzerResource $unzerCustomer,
        SalesChannelContext $salesChannelContext
    ): AbstractUnzerResource {
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
        $address->setState(
            $shopwareAddress->getCountryState() !== null ? $shopwareAddress->getCountryState()->getShortCode() : null
        );
        $address->setZip($shopwareAddress->getZipcode());
        $address->setStreet($shopwareAddress->getStreet());
        $address->setCity($shopwareAddress->getCity());
        $address->setName(sprintf('%s %s', $shopwareAddress->getFirstName(), $shopwareAddress->getLastName()));

        return $address;
    }

    protected function addAdditionalDataToCustomer(
        Customer $unzerCustomer,
        CustomerEntity $customer,
        CustomerAddressEntity $billingAddress
    ): Customer {
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
            $unzerCustomer->setSalutation(
                $customer->getSalutation() !== null ? $customer->getSalutation()->getSalutationKey() : null
            );
        }

        if (empty($unzerCustomer->getBirthDate())) {
            $unzerCustomer->setBirthDate($this->getBirthDate($customer));
        }

        if (empty($unzerCustomer->getCompany()) && !empty($billingAddress->getCompany())) {
            $unzerCustomer->setCompany($billingAddress->getCompany());
        }

        return $unzerCustomer;
    }

    protected function getBirthDate(CustomerEntity $customer): ?string
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        if ($currentRequest !== null) {
            $requestBirthday = $currentRequest->get('unzerPaymentBirthday', '');

            if (!empty($requestBirthday)) {
                return $requestBirthday;
            }
        }

        return $customer->getBirthday() !== null ? $customer->getBirthday()->format('Y-m-d') : null;
    }
}
