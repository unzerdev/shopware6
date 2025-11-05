<?php declare(strict_types=1);

namespace UnzerPayment6\Components\ExpressCheckout;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\SalesChannelContextSwitcher;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\Payment;

class ExpressCheckoutService
{
    public const ADDRESS_PART_PLACEHOLDER = '-----';
    public const SESSION_PAYPAL_PAYMENT_ID = 'paypal-express-checkout-payment-id';
    public const SESSION_PAYPAL_PAYMENT_TYPE_ID = 'paypal-express-checkout-payment-type-id';
    public const SESSION_GOOGLE_PAYMENT_TYPE_ID = 'google-express-checkout-payment-type-id';
    public const SESSION_APPLEPAY_PAYMENT_TYPE_ID = 'applepay-express-checkout-payment-type-id';
    public const SESSION_SELECTED_EXPRESS_METHOD = 'express-checkout-payment-id-selected';

    public function __construct(
        private readonly CartService $cartService,
        private AbstractRegisterRoute $registerRoute,
        private EntityRepository $countryRepository,
        private EntityRepository $customerRepository,
        private EntityRepository $salutationRepository,
        private EntityRepository $customerAddressRepository,
        private SalesChannelContextPersister $salesChannelContextPersister,
        private SalesChannelContextSwitcher $salesChannelContextSwitcher,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getCart(SalesChannelContext $salesChannelContext): Cart
    {
        return $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
    }

    public function startCheckoutSessionFromUnzerPayment(Payment $payment, ?string $paymentMethodId, SalesChannelContext $salesChannelContext): void
    {
        $data = $this->getRegisterDataFromUnzerPayment($payment, $salesChannelContext);
        $this->startCheckoutDataWithNormalizedCustomerData($data, $paymentMethodId, $salesChannelContext);
    }

    public function startCheckoutSessionFromGooglePayData(array $paymentData, ?string $paymentMethodId, SalesChannelContext $salesChannelContext): void
    {
        $data = $this->getRegisterDataFromGooglePayData($paymentData, $salesChannelContext);
        $this->startCheckoutDataWithNormalizedCustomerData($data, $paymentMethodId, $salesChannelContext);
    }

    public function startCheckoutSessionFromApplePayData(array $paymentData, array $shippingContact, array $billingContact, ?string $paymentMethodId, SalesChannelContext $salesChannelContext): void
    {
        $data = $this->getRegisterDataFromApplePayData($shippingContact, $billingContact, $salesChannelContext);
        $this->startCheckoutDataWithNormalizedCustomerData($data, $paymentMethodId, $salesChannelContext);
    }

    public function setPaymentMethod(string $paymentMethodId, CustomerEntity $customer, SalesChannelContext $salesChannelContext): void
    {
        $this->customerRepository->upsert([
            [
                'id' => $customer->getId(),
                'defaultPaymentMethodId' => $paymentMethodId,
            ],
        ], $salesChannelContext->getContext());
        $this->salesChannelContextSwitcher->update(
            new RequestDataBag([
                SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethodId,
            ]),
            $salesChannelContext
        );
    }

    private function startCheckoutDataWithNormalizedCustomerData(?DataBag $data, ?string $paymentMethodId, SalesChannelContext $salesChannelContext): void
    {
        if ($data === null) {
            throw new \Exception('no customer data');
        }
        if ($salesChannelContext->getCustomer()) {
            $this->updateShippingAddress($data->get('shippingAddress'), $salesChannelContext->getCustomer()->getId(), $salesChannelContext->getContext());
            if ($paymentMethodId) {
                $this->setPaymentMethod($paymentMethodId, $salesChannelContext->getCustomer(), $salesChannelContext);
            }
        } else {
            $result = $this->registerRoute->register(
                $data->toRequestDataBag(),
                $salesChannelContext,
                false
            );

            if ($result->getCustomer()) {
                if ($paymentMethodId) {
                    $this->setPaymentMethod($paymentMethodId, $result->getCustomer(), $salesChannelContext);
                }
                $this->login($result->getCustomer(), $salesChannelContext);
            }
        }
    }

    private function getRegisterDataFromGooglePayData(array $paymentData, SalesChannelContext $salesChannelContext): ?DataBag
    {
        $data = new DataBag();
        $password = Uuid::randomHex();
        $name = $paymentData['paymentMethodData']['info']['billingAddress']['name'] ?? '---- ----';
        $names = $this->separateName($name);

        $shippingAddress = $this->getAddressFromGoogleData($paymentData['shippingAddress'] ?? [], $salesChannelContext->getContext());
        $billingAddress = $this->getAddressFromGoogleData($paymentData['paymentMethodData']['info']['billingAddress'] ?? [], $salesChannelContext->getContext());
        if (!$this->isAddressDataBagComplete($billingAddress)) {
            $billingAddress = $shippingAddress;
        }

        $data->add([
            'firstName' => $names['firstName'],
            'lastName' => $names['lastName'],
            'guest' => true,
            'email' => $paymentData['email'] ?? '',
            'salutationId' => $this->getDefaultSalutationId($salesChannelContext->getContext()),
            'acceptedDataProtection' => true,
            'password' => $password,
            'passwordConfirmation' => $password,
            'billingAddress' => $billingAddress,
            'shippingAddress' => $shippingAddress,
        ]);

        return $data;
    }

    private function getRegisterDataFromApplePayData(array $shippingContact, array $billingContact, SalesChannelContext $salesChannelContext): ?DataBag
    {
        $data = new DataBag();
        $password = Uuid::randomHex();
        $firstName = $shippingContact['givenName'] === '' ? '----' : $shippingContact['givenName'];
        $lastName = $shippingContact['familyName'] === '' ? '----' : $shippingContact['familyName'];

        $shippingAddress = $this->getAddressFromApplePayData($shippingContact ?? [], $salesChannelContext->getContext());
        $billingAddress = $this->getAddressFromApplePayData($billingContact ?? [], $salesChannelContext->getContext());
        if (!$this->isAddressDataBagComplete($billingAddress)) {
            $billingAddress = $shippingAddress;
        }

        $data->add([
            'firstName' => $firstName,
            'lastName' => $lastName,
            'guest' => true,
            'email' => $shippingContact['emailAddress'] ?? '',
            'salutationId' => $this->getDefaultSalutationId($salesChannelContext->getContext()),
            'acceptedDataProtection' => true,
            'password' => $password,
            'passwordConfirmation' => $password,
            'billingAddress' => $billingAddress,
            'shippingAddress' => $shippingAddress,
        ]);

        return $data;
    }

    private function getRegisterDataFromUnzerPayment(Payment $payment, SalesChannelContext $salesChannelContext): ?DataBag
    {
        $data = new DataBag();

        $customer = $payment->getCustomer();

        if ($customer === null) {
            return null;
        }

        $shippingAddress = $this->getAddressFromUnzerCustomer($customer, $salesChannelContext->getContext(), 'shipping');
        $billingAddress = $this->getAddressFromUnzerCustomer($customer, $salesChannelContext->getContext());
        if (!$this->isAddressDataBagComplete($billingAddress)) {
            $billingAddress = $shippingAddress;
        }

        $password = Uuid::randomHex();
        $data->add([
            'firstName' => $customer->getFirstname(),
            'lastName' => $customer->getLastname(),
            'guest' => true,
            'email' => $customer->getEmail(),
            'salutationId' => $this->getDefaultSalutationId($salesChannelContext->getContext()),
            'acceptedDataProtection' => true,
            'password' => $password,
            'passwordConfirmation' => $password,
            'billingAddress' => $billingAddress,
            'shippingAddress' => $shippingAddress,
        ]);

        return $data;
    }

    private function isAddressDataBagComplete(DataBag $dataBag): bool
    {
        foreach ($dataBag->all() as $key => $value) {
            if ($value === self::ADDRESS_PART_PLACEHOLDER) {
                return false;
            }
        }

        return true;
    }

    private function getAddressFromGoogleData(array $address, Context $context): ?DataBag
    {
        $nameParts = $this->separateName($address['name'] ?? self::ADDRESS_PART_PLACEHOLDER . ' ' . self::ADDRESS_PART_PLACEHOLDER);

        return new DataBag([
            'salutationId' => $this->getDefaultSalutationId($context),
            'firstName' => $nameParts['firstName'],
            'lastName' => $nameParts['lastName'],
            'city' => $address['locality'] ?? self::ADDRESS_PART_PLACEHOLDER,
            'countryId' => $this->getCountryId($address['countryCode'], $context),
            'street' => $address['address1'] ?? self::ADDRESS_PART_PLACEHOLDER,
            'additionalAddressLine1' => $address['address2'] ?? null,
            'additionalAddressLine2' => $address['address3'] ?? null,
            'zipcode' => $address['postalCode'] ?? self::ADDRESS_PART_PLACEHOLDER,
            'phoneNumber' => '',
            'company' => '',
        ]);
    }

    private function getAddressFromApplePayData(array $address, Context $context): ?DataBag
    {
        return new DataBag([
            'salutationId' => $this->getDefaultSalutationId($context),
            'firstName' => $address['givenName'] === '' ? '----' : $address['givenName'],
            'lastName' => $address['familyName'] === '' ? '----' : $address['familyName'],
            'city' => $address['locality'] ?? self::ADDRESS_PART_PLACEHOLDER,
            'countryId' => $this->getCountryId($address['countryCode'], $context),
            'street' => $address['addressLines'][0] ?? self::ADDRESS_PART_PLACEHOLDER,
            'additionalAddressLine1' => $address['addressLines'][1] ?? null,
            'additionalAddressLine2' => $address['addressLines'][2] ?? null,
            'zipcode' => $address['postalCode'] ?? self::ADDRESS_PART_PLACEHOLDER,
            'phoneNumber' => $address['phoneNumber'] ?? '',
            'company' => '',
        ]);
    }

    private function getAddressFromUnzerCustomer(Customer $customer, Context $context, string $addressType = 'billing'): ?DataBag
    {
        $address = ($addressType === 'billing' ? $customer->getBillingAddress() : $customer->getShippingAddress());

        if (empty($address->getZip())) {
            $address->setZip(self::ADDRESS_PART_PLACEHOLDER);
        }
        if (empty($address->getCity())) {
            $address->setCity(self::ADDRESS_PART_PLACEHOLDER);
        }
        if (empty($address->getStreet())) {
            $address->setStreet(self::ADDRESS_PART_PLACEHOLDER);
        }

        $nameParts = $this->separateName($address->getName());

        return new DataBag([
            'salutationId' => $this->getDefaultSalutationId($context),
            'firstName' => $nameParts['firstName'],
            'lastName' => $nameParts['lastName'],
            'city' => $address->getCity(),
            'countryId' => $this->getCountryId($address->getCountry(), $context),
            'street' => $address->getStreet(),
            'zipcode' => $address->getZip(),
            'phoneNumber' => '',
            'company' => '',
        ]);
    }

    private function getCountryId(string $countryCode, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(
            new EqualsFilter('iso', $countryCode)
        );

        return $this->countryRepository->searchIds($criteria, $context)->firstId();
    }

    private function login(CustomerEntity $customer, SalesChannelContext $salesChannelContext): string
    {
        $newToken = $this->salesChannelContextPersister->replace($salesChannelContext->getToken(), $salesChannelContext);
        $this->salesChannelContextPersister->save(
            $newToken,
            ['billingAddressId' => null, 'shippingAddressId' => null],
            $salesChannelContext->getSalesChannel()->getId(),
            $customer->getId()
        );

        $event = new CustomerLoginEvent($salesChannelContext, $customer, $newToken);
        $this->eventDispatcher->dispatch($event);

        return $newToken;
    }

    private function getDefaultSalutationId(Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(
            new EqualsFilter('salutationKey', SalutationDefinition::NOT_SPECIFIED)
        );

        return $this->salutationRepository->searchIds($criteria, $context)->firstId();
    }

    private function separateName(string $name): array
    {
        $exploded = explode(' ', $name);
        if (\count($exploded) > 1) {
            $lastName = array_pop($exploded);

            return [
                'firstName' => implode(' ', $exploded),
                'lastName' => $lastName,
            ];
        }

        return [
            'firstName' => $exploded[0],
            'lastName' => '.',
        ];
    }

    private function updateShippingAddress(DataBag $address, string $customerId, Context $context): void
    {
        $addressId = $this->getExistingCustomerAddressId($customerId, $address, $context);

        if ($addressId) {
            $this->customerRepository->upsert([
                [
                    'id' => $customerId,
                    'defaultShippingAddressId' => $addressId,
                ],
            ], $context);

            return;
        }

        $this->customerRepository->upsert([
            [
                'id' => $customerId,
                'defaultShippingAddress' => $address->all(),
            ],
        ], $context);
    }

    private function getExistingCustomerAddressId(string $customerId, DataBag $address, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('customerId', $customerId),
            new EqualsFilter('firstName', $address->get('firstName')),
            new EqualsFilter('lastName', $address->get('lastName')),
            new EqualsFilter('city', $address->get('city')),
            new EqualsFilter('zipcode', $address->get('zipcode')),
            new EqualsFilter('street', $address->get('street')),
            new EqualsFilter('countryId', $address->get('countryId')),
        );

        return $this->customerAddressRepository->searchIds($criteria, $context)->firstId();
    }
}
