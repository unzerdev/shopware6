<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator;

use RuntimeException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerSDK\Constants\ShippingTypes;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\Address;

class CustomerResourceHydrator implements CustomerResourceHydratorInterface
{
    private const B2B_CUSTOMERS_ALLOWED = [
        PaymentInstaller::PAYMENT_ID_INVOICE_SECURED,
        PaymentInstaller::PAYMENT_ID_PAYLATER_INVOICE,
        PaymentInstaller::PAYMENT_ID_DIRECT_DEBIT_SECURED,
    ];

    private RequestStack $requestStack;

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

        if (empty($billingAddress) || empty($shippingAddress)) {
            throw new RuntimeException(sprintf('Could not determine the address for customer with number %s', $customer->getCustomerNumber()));
        }

        if (empty($billingAddress->getCompany()) || !in_array($paymentMethodId, self::B2B_CUSTOMERS_ALLOWED, true)) {
            $unzerCustomer = CustomerFactory::createCustomer(
                $billingAddress->getFirstName(),
                $billingAddress->getLastName()
            );
        } else {
            $unzerCustomer = CustomerFactory::createNotRegisteredB2bCustomer(
                $billingAddress->getFirstName(),
                $billingAddress->getLastName(),
                $this->getBirthDate($customer),
                $this->getUnzerAddress($billingAddress),
                $customer->getEmail(),
                $billingAddress->getCompany()
            );
        }

        $unzerCustomer->setShippingAddress($this->getUnzerAddress($shippingAddress));
        $unzerCustomer->setBillingAddress($this->getUnzerAddress($billingAddress));

        $customerNumber = $customer->getCustomerNumber();

        if (!empty($billingAddress->getCompany())) {
            $customerNumber .= '_b';
        }

        $unzerCustomer->setCustomerId($customerNumber);

        return $this->updateAdditionalDataToCustomer($unzerCustomer, $customer, $billingAddress);
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

        return $this->updateAdditionalDataToCustomer($unzerCustomer, $customer, $billingAddress);
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

    /**
     * @deprecated this function will be removed in a future update. Please use \UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator\CustomerResourceHydrator::updateAdditionalDataToCustomer instead
     */
    protected function addAdditionalDataToCustomer(
        Customer $unzerCustomer,
        CustomerEntity $customer,
        CustomerAddressEntity $billingAddress
    ): Customer {
        return $this->updateAdditionalDataToCustomer($unzerCustomer, $customer, $billingAddress);
    }

    protected function updateAdditionalDataToCustomer(
        Customer $unzerCustomer,
        CustomerEntity $customer,
        CustomerAddressEntity $billingAddress
    ): Customer {
        $unzerBillingAddress = $unzerCustomer->getBillingAddress();

        if ($unzerCustomer->getFirstname() !== $billingAddress->getFirstName()) {
            $unzerCustomer->setFirstname($billingAddress->getFirstName());
            $unzerBillingAddress->setName($billingAddress->getFirstName() . ' ' . $billingAddress->getLastName());
        }

        if ($unzerCustomer->getLastname() !== $billingAddress->getLastName()) {
            $unzerCustomer->setLastname($billingAddress->getLastName());
            $unzerBillingAddress->setName($billingAddress->getFirstName() . ' ' . $billingAddress->getLastName());
        }

        if ($unzerCustomer->getEmail() !== $customer->getEmail()) {
            $unzerCustomer->setEmail($customer->getEmail());
        }

        if ($billingAddress->getSalutation() !== null && $unzerCustomer->getSalutation() !== $billingAddress->getSalutation()->getSalutationKey()) {
            $unzerCustomer->setSalutation(
                $billingAddress->getSalutation()->getSalutationKey()
            );
        }

        $birthdate = $this->getBirthDate($customer);

        if ($unzerCustomer->getBirthDate() !== $birthdate) {
            $unzerCustomer->setBirthDate($birthdate);
        }

        if ($unzerCustomer->getCompany() !== $billingAddress->getCompany()) {
            $unzerCustomer->setCompany($billingAddress->getCompany());
        }

        if ($unzerBillingAddress->getStreet() !== $billingAddress->getStreet()) {
            $unzerBillingAddress->setStreet($billingAddress->getStreet());
        }

        if ($unzerBillingAddress->getCity() !== $billingAddress->getCity()) {
            $unzerBillingAddress->setCity($billingAddress->getCity());
        }

        if ($unzerBillingAddress->getZip() !== $billingAddress->getZipcode()) {
            $unzerBillingAddress->setZip($billingAddress->getZipcode());
        }

        if ($billingAddress->getCountry() !== null && $unzerBillingAddress->getCountry() !== $billingAddress->getCountry()->getIso()) {
            $unzerBillingAddress->setCountry($billingAddress->getCountry()->getIso());
        }

        $unzerCustomer->setBillingAddress($unzerBillingAddress);
        $this->updateShippingAddress($unzerCustomer, $customer->getActiveShippingAddress(), $billingAddress->getId());

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

    private function updateShippingAddress(Customer $unzerCustomer, ?CustomerAddressEntity $shippingAddress, string $billingAddressId): void
    {
        $unzerShippingAddress = $unzerCustomer->getShippingAddress();

        if ($shippingAddress === null) {
            return;
        }

        $name = $shippingAddress->getFirstName() . ' ' . $shippingAddress->getLastName();

        if ($unzerShippingAddress->getName() !== $name) {
            $unzerShippingAddress->setName($name);
        }

        if ($unzerShippingAddress->getStreet() !== $shippingAddress->getStreet()) {
            $unzerShippingAddress->setStreet($shippingAddress->getStreet());
        }

        if ($unzerShippingAddress->getCity() !== $shippingAddress->getCity()) {
            $unzerShippingAddress->setCity($shippingAddress->getCity());
        }

        if ($unzerShippingAddress->getZip() !== $shippingAddress->getZipcode()) {
            $unzerShippingAddress->setZip($shippingAddress->getZipcode());
        }

        if ($shippingAddress->getCountry() !== null && $unzerShippingAddress->getCountry() !== $shippingAddress->getCountry()->getIso()) {
            $unzerShippingAddress->setCountry($shippingAddress->getCountry()->getIso());
        }

        $shippingType = $billingAddressId === $shippingAddress->getId()
            ? ShippingTypes::EQUALS_BILLING
            : ShippingTypes::DIFFERENT_ADDRESS;

        $unzerShippingAddress->setShippingType($shippingType);

        $unzerCustomer->setShippingAddress($unzerShippingAddress);
    }
}
