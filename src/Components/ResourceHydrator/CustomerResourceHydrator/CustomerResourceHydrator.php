<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\App\ShopId\ShopIdProvider;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Component\HttpFoundation\RequestStack;
use UnzerPayment6\Installer\PaymentInstaller;
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
        private RequestStack $requestStack,
        private EntityRepository $customerRepository,
        protected EntityRepository $languageRepository,
        protected ShopIdProvider $shopIdProvider,
    ) {
    }

    public function getShopCustomerId(CustomerEntity $customer, ?OrderAddressEntity $orderBillingAddress = null): string
    {
        $customerNumber = $customer->getCustomerNumber();
        $company = $orderBillingAddress !== null ? $orderBillingAddress->getCompany() : $customer->getActiveBillingAddress()?->getCompany();

        if (!empty($company)) {
            $customerNumber .= '_b';
        }

        try {
            $shopId = $this->shopIdProvider->getShopId();
        } catch (\Throwable) {
            $shopId = '';
        }
        $customerNumber .= '_' . $shopId;

        return $customerNumber;
    }

    public function getCustomer(string $customerId, Context $context): ?CustomerEntity
    {
        return $this->customerRepository->search(
            (new Criteria([$customerId]))
                ->addAssociations([
                    'activeBillingAddress.country',
                    'activeShippingAddress.countryState',
                    'salutation',
                    'addresses',
                ]),
            $context
        )->first();
    }

    /**
     * Creates a new prefilled Unzer Customer Object from the current SalesChannelContext
     */
    public function hydrateObject(
        string $paymentMethodId,
        OrderCustomerEntity|CustomerEntity $customer,
        Context $context,
        ?OrderTransactionEntity $orderTransaction = null
    ): UnzerCustomer {
        if ($customer instanceof OrderCustomerEntity) {
            $customer = $this->getCustomer($customer->getCustomerId(), $context);
        }

        if (!$customer) {
            throw new \RuntimeException('Could not determine the customer');
        }

        if ($orderTransaction !== null) {
            $billingAddress = $orderTransaction->getOrder()->getBillingAddress();
            $shippingAddress = $orderTransaction->getOrder()->getDeliveries()->first()->getShippingOrderAddress();

            if (empty($billingAddress) || empty($shippingAddress)) {
                throw new \RuntimeException(\sprintf('Could not determine any address for order %s', $orderTransaction->getOrder()->getOrderNumber()));
            }
        } else {
            $billingAddress = $customer->getActiveBillingAddress();
            $shippingAddress = $customer->getActiveShippingAddress();
            if (empty($billingAddress) || empty($shippingAddress)) {
                throw new \RuntimeException(\sprintf('Could not determine the address for customer with number %s', $customer->getCustomerNumber()));
            }
        }

        if (empty($billingAddress->getCompany()) || !\in_array($paymentMethodId, self::B2B_CUSTOMERS_ALLOWED, true)) {
            $unzerCustomer = CustomerFactory::createCustomer(
                $billingAddress->getFirstName(),
                $billingAddress->getLastName()
            );
        } else {
            $unzerCustomer = CustomerFactory::createNotRegisteredB2bCustomer(
                $billingAddress->getFirstName(),
                $billingAddress->getLastName(),
                (string) $this->getBirthDate($customer),
                $this->getUnzerAddress($billingAddress),
                $customer->getEmail(),
                $billingAddress->getCompany()
            );
        }

        $unzerCustomer->setShippingAddress($this->getUnzerAddress($shippingAddress));
        $unzerCustomer->setBillingAddress($this->getUnzerAddress($billingAddress));
        $unzerCustomer->setCustomerId($this->getShopCustomerId($customer));

        return $this->updateAdditionalDataToCustomer($unzerCustomer, $customer, $billingAddress, $orderTransaction);
    }

    public function hydrateExistingCustomer(
        UnzerCustomer $unzerCustomer,
        OrderCustomerEntity|CustomerEntity $customer,
        Context $context,
        ?OrderTransactionEntity $orderTransaction = null
    ): UnzerCustomer {
        if ($customer instanceof OrderCustomerEntity) {
            $customer = $this->getCustomer($customer->getCustomerId(), $context);
        }

        if (!$customer) {
            throw new \RuntimeException('Could not determine the customer');
        }

        if ($orderTransaction !== null) {
            $billingAddress = $orderTransaction->getOrder()->getBillingAddress();
        } else {
            $billingAddress = $customer->getActiveBillingAddress();
        }

        if (!$billingAddress) {
            throw new \RuntimeException(\sprintf('Could not determine the address for customer with number %s', $customer->getCustomerNumber()));
        }

        return $this->updateAdditionalDataToCustomer($unzerCustomer, $customer, $billingAddress, $orderTransaction);
    }

    protected function getUnzerAddress(OrderAddressEntity|CustomerAddressEntity $shopwareAddress): Address
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
        UnzerCustomer $unzerCustomer,
        CustomerEntity $customer,
        OrderAddressEntity|CustomerAddressEntity $billingAddress,
        ?OrderTransactionEntity $orderTransaction = null
    ): UnzerCustomer {
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

        $updatedCompany = false;
        if ($unzerCustomer->getCompany() !== $billingAddress->getCompany()) {
            $unzerCustomer->setCompany($billingAddress->getCompany());
            $updatedCompany = true;
        }

        if (empty($unzerCustomer->getCompany())) {
            $unzerCustomer->setCompanyInfo(null);
        } else {
            $companyInfo = $unzerCustomer->getCompanyInfo();
            if (empty($companyInfo)) {
                $companyInfo = new CompanyInfo();
            }
            if (empty($companyInfo->getCompanyType()) || $updatedCompany) {
                $companyInfo->setCompanyType('Company Type');
            }
            //            if (empty($companyInfo->getRegistrationType()) || $updatedCompany) {
            //                $companyInfo->setRegistrationType('not_registered');
            //            }
            if (empty($companyInfo->getFunction()) || $updatedCompany) {
                $companyInfo->setFunction('OWNER');
            }
            if (empty($companyInfo->getCommercialSector()) || $updatedCompany) {
                $companyInfo->setCommercialSector('OTHER');
            }
            $unzerCustomer->setCompanyInfo($companyInfo);
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

        $shippingAddress = $orderTransaction !== null ? $orderTransaction->getOrder()?->getDeliveries()?->first()?->getShippingOrderAddress() : $customer->getActiveShippingAddress();
        $this->updateShippingAddress($unzerCustomer, $shippingAddress, $billingAddress->getId());

        $languageCriteria = new Criteria([$customer->getLanguageId()]);
        $languageCriteria->addAssociation('locale');
        /** @var LanguageEntity $language */
        $language = $this->languageRepository->search($languageCriteria, Context::createDefaultContext())->first();
        if ($language !== null) {
            if ($locale = $language->getLocale()) {
                $localCode = $locale->getCode();
                $unzerCustomer->setLanguage(strtolower(substr($localCode, 0, 2)));
            }
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

        return $customer->getBirthday()?->format('Y-m-d');
    }

    private function updateShippingAddress(UnzerCustomer $unzerCustomer, OrderAddressEntity|CustomerAddressEntity|null $shippingAddress, string $billingAddressId): void
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
