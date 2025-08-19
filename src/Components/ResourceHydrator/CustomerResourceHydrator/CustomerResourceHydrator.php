<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator;

use RuntimeException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\HttpFoundation\RequestStack;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerSDK\Constants\CompanyCommercialSectorItems;
use UnzerSDK\Constants\CompanyRegistrationTypes;
use UnzerSDK\Constants\ShippingTypes;
use UnzerSDK\Resources\Customer as UnzerCustomer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\Address;
use UnzerSDK\Resources\EmbeddedResources\CompanyInfo;

readonly class CustomerResourceHydrator implements CustomerResourceHydratorInterface
{
    private const B2B_CUSTOMERS_ALLOWED = [
        PaymentInstaller::PAYMENT_ID_INVOICE_SECURED,
        PaymentInstaller::PAYMENT_ID_PAYLATER_INVOICE,
        PaymentInstaller::PAYMENT_ID_DIRECT_DEBIT_SECURED,
    ];


    public function __construct(
        private RequestStack     $requestStack,
        private EntityRepository $customerRepository
    )
    {

    }

    public function getCustomer(string $customerId, Context $context): ?CustomerEntity
    {
        return $this->customerRepository->search(
            (new Criteria([$customerId]))
                ->addAssociations([
                    'activeBillingAddress',
                    'activeShippingAddress',
                    'salutation',
                    'addresses',
                ]),
            $context
        )->first();

    }

    public function hydrateObject(
        string                 $paymentMethodId,
        OrderCustomerEntity    $orderCustomer,
        OrderTransactionEntity $orderTransaction,
        Context                $context
    ): UnzerCustomer
    {
        $customer = $this->getCustomer($orderCustomer->getCustomerId(), $context);
        if (!$customer) {
            throw new RuntimeException('Could not determine the customer');
        }

        $billingAddress = $orderTransaction->getOrder()->getBillingAddress();
        $shippingAddress = $orderTransaction->getOrder()->getDeliveries()->first()->getShippingOrderAddress();

        if (empty($billingAddress) || empty($shippingAddress)) {
            throw new RuntimeException(\sprintf('Could not determine any address for order %s', $orderTransaction->getOrder()->getOrderNumber()));
        }

        if (empty($billingAddress->getCompany()) || !\in_array($paymentMethodId, self::B2B_CUSTOMERS_ALLOWED, true)) {
            $unzerCustomer = CustomerFactory::createCustomer(
                $billingAddress->getFirstName(),
                $billingAddress->getLastName()
            );
        } else {
            $companyInfo = (new CompanyInfo())
                ->setRegistrationType(CompanyRegistrationTypes::REGISTRATION_TYPE_NOT_REGISTERED)
                ->setFunction('OWNER')
                ->setCommercialSector(CompanyCommercialSectorItems::OTHER);

            $unzerCustomer = (new UnzerCustomer())
                ->setFirstname($billingAddress->getFirstName())
                ->setLastname($billingAddress->getLastName())
                ->setBirthDate($this->getBirthDate($customer))
                ->setBillingAddress($this->getUnzerAddress($billingAddress))
                ->setEmail($customer->getEmail())
                ->setCompany($billingAddress->getCompany())
                ->setCompanyInfo($companyInfo);
        }

        $unzerCustomer->setShippingAddress($this->getUnzerAddress($shippingAddress));
        $unzerCustomer->setBillingAddress($this->getUnzerAddress($billingAddress));

        $customerNumber = $customer->getCustomerNumber();

        if (!empty($billingAddress->getCompany())) {
            $customerNumber .= '_b';
        }

        $unzerCustomer->setCustomerId($customerNumber);

        return $this->updateAdditionalDataToCustomer($unzerCustomer, $customer, $orderTransaction, $billingAddress);
    }

    public function hydrateExistingCustomer(
        UnzerCustomer          $unzerCustomer,
        OrderCustomerEntity    $orderCustomer,
        OrderTransactionEntity $orderTransaction,
        Context                $context
    ): UnzerCustomer
    {
        $customer = $this->getCustomer($orderCustomer->getCustomerId(), $context);

        if (!$customer) {
            throw new RuntimeException('Could not determine the customer');
        }

        $billingAddress = $orderTransaction->getOrder()->getBillingAddress();

        if (!$billingAddress) {
            throw new RuntimeException(\sprintf('Could not determine the address for customer with number %s', $customer->getCustomerNumber()));
        }

        return $this->updateAdditionalDataToCustomer($unzerCustomer, $customer, $orderTransaction, $billingAddress);
    }

    protected function getUnzerAddress(OrderAddressEntity $shopwareAddress): Address
    {
        $address = new Address();
        $address->setCountry($shopwareAddress->getCountry()?->getIso());
        $address->setState($shopwareAddress->getCountryState()?->getShortCode());
        $address->setZip($shopwareAddress->getZipcode());
        $address->setStreet($shopwareAddress->getStreet());
        $address->setCity($shopwareAddress->getCity());
        $address->setName(\sprintf('%s %s', $shopwareAddress->getFirstName(), $shopwareAddress->getLastName()));

        return $address;
    }


    protected function updateAdditionalDataToCustomer(
        UnzerCustomer          $unzerCustomer,
        CustomerEntity         $customer,
        OrderTransactionEntity $orderTransaction,
        OrderAddressEntity     $billingAddress
    ): UnzerCustomer
    {
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
        $this->updateShippingAddress($unzerCustomer, $orderTransaction->getOrder()?->getDeliveries()?->first()?->getShippingOrderAddress(), $billingAddress->getId());

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

        return $customer->getBirthday()?->format('Y-m-d');
    }

    private function updateShippingAddress(UnzerCustomer $unzerCustomer, ?OrderAddressEntity $shippingAddress, string $billingAddressId): void
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
