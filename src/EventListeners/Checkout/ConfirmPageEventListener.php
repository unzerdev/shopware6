<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\Checkout;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\ConfigReader\KeyPairConfigReader;
use UnzerPayment6\Components\ExpressCheckout\ExpressCheckoutService;
use UnzerPayment6\Components\PaymentFrame\PaymentFrameFactoryInterface;
use UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator\CustomerResourceHydratorInterface;
use UnzerPayment6\Components\Storefront\ExtensionFactory;
use UnzerPayment6\Components\Struct\Configuration;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\ApplePayV2PageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\CreditCardPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\DirectDebitPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\DirectDebitSecuredPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\GooglePayPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\InstallmentSecuredPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\PaylaterDirectDebitSecuredPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\PaylaterInstallmentPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\PaymentFramePageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\PayPalPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\UnzerDataPageExtension;
use UnzerPayment6\Components\UnzerUtil\UnzerApiUtil;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerSDK\Resources\Customer;

class ConfirmPageEventListener implements EventSubscriberInterface
{
    private ?Configuration $configData = null;

    public function __construct(
        private readonly UnzerPaymentDeviceRepositoryInterface $deviceRepository,
        private readonly ConfigReaderInterface $configReader,
        private readonly PaymentFrameFactoryInterface $paymentFrameFactory,
        private readonly SystemConfigService $systemConfigReader,
        private readonly EntityRepository $languageRepository,
        private readonly ClientFactoryInterface $clientFactory,
        private readonly KeyPairConfigReader $keyPairConfigReader,
        private readonly ExtensionFactory $extensionFactory,
        private readonly CustomerResourceHydratorInterface $customerResourceHydrator,
        private readonly UnzerApiUtil $unzerApiUtil,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirm',
            AccountEditOrderPageLoadedEvent::class => 'onCheckoutConfirm',
        ];
    }

    public function onCheckoutConfirm(PageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $paymentMethod = $salesChannelContext->getPaymentMethod();

        if (!$this->isActionRequired($event, $paymentMethod)) {
            return;
        }
        $paymentMethodId = $paymentMethod->getId();
        $this->configData = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        switch ($paymentMethodId) {
            case PaymentInstaller::PAYMENT_ID_CREDIT_CARD:
                $this->addCreditCardExtension($event);
                break;
            case PaymentInstaller::PAYMENT_ID_PAYPAL:
                $this->addPayPalExtension($event);
                break;
            case PaymentInstaller::PAYMENT_ID_DIRECT_DEBIT:
                $this->addDirectDebitExtension($event);
                break;
            case PaymentInstaller::PAYMENT_ID_DIRECT_DEBIT_SECURED:
                $this->addDirectDebitSecuredExtension($event);
                break;
            case PaymentInstaller::PAYMENT_ID_INSTALLMENT_SECURED:
                $this->addInstallmentSecuredExtension($event);
                break;
            case PaymentInstaller::PAYMENT_ID_APPLE_PAY_V2:
                $this->addApplePayV2Extension($event);
                break;
            case PaymentInstaller::PAYMENT_ID_PAYLATER_INSTALLMENT:
                $this->addPaylaterInstallmentExtension($event);
                break;
            case PaymentInstaller::PAYMENT_ID_PAYLATER_DIRECT_DEBIT_SECURED:
                $this->addPaylaterDirectDebitSecuredExtension($event);
                break;
            case PaymentInstaller::PAYMENT_ID_GOOGLE_PAY:
                $this->addGooglePayExtension($event);
                break;
        }

        if (\in_array($paymentMethodId, PaymentInstaller::PAYMENT_METHOD_IDS, true)) {
            $this->addPaymentFrameExtension($event);
            $this->addUnzerDataExtension($event);
        }
    }

    private function isActionRequired(PageLoadedEvent $event, PaymentMethodEntity $paymentMethod): bool
    {
        return $event instanceof CheckoutConfirmPageLoadedEvent || ($event instanceof AccountEditOrderPageLoadedEvent && $paymentMethod->getAfterOrderEnabled());
    }

    private function addUnzerDataExtension(PageLoadedEvent $event): void
    {
        $context = $event->getSalesChannelContext()->getContext();

        $extension = new UnzerDataPageExtension();
        $publicKey = $this->getPublicKey($event->getSalesChannelContext());
        $extension->setPublicKey($publicKey);
        $extension->setLocale($this->getLocaleByLanguageId($context->getLanguageId(), $context));
        $extension->setShowTestData((bool) $this->configData->get(ConfigReader::CONFIG_KEY_TEST_DATA));
        $extension->setBlockButtonOnLoad((bool) $this->configData->get(ConfigReader::CONFIG_KEY_BLOCK_CONFIRM_BUTTON_ON_LOAD));
        $extension->setUnzerCustomer($this->getUnzerCustomer($event));
        $extension->setKeyPairConfig($this->unzerApiUtil->getCachedKeypairConfig($publicKey));
        $event->getPage()->addExtension(UnzerDataPageExtension::EXTENSION_NAME, $extension);
    }

    private function getUnzerCustomer(PageLoadedEvent $event): ?Customer
    {
        $shopwareCustomer = $event->getSalesChannelContext()->getCustomer();

        if ($shopwareCustomer === null) {
            return null;
        }

        $client = $this->clientFactory->createClientFromSalesChannelContext($event->getSalesChannelContext(), $event->getRequest());
        $customerNumber = $this->customerResourceHydrator->getShopCustomerId($shopwareCustomer);
        try {
            $existingCustomer = $client->fetchCustomerByExtCustomerId($customerNumber);
            /** @var Customer $existingCustomer */
            $existingCustomer = $this->customerResourceHydrator->hydrateExistingCustomer($existingCustomer, $shopwareCustomer, $event->getSalesChannelContext()->getContext());
            $customer = $client->updateCustomer($existingCustomer);
        } catch (\Throwable) {
            $customer = null;
        }

        if (empty($customer)) {
            try {
                // create one if not existing
                $newCustomer = $this->customerResourceHydrator->hydrateObject(
                    $event->getSalesChannelContext()->getPaymentMethod()->getId(),
                    $shopwareCustomer,
                    $event->getSalesChannelContext()->getContext()
                );
                $customer = $client->createCustomer($newCustomer);
            } catch (\Throwable) {
                $customer = null;
            }
        }

        return $customer;
    }

    private function addPaymentFrameExtension(PageLoadedEvent $event): void
    {
        $paymentId = $event->getSalesChannelContext()->getPaymentMethod()->getId();
        $mappedFrameTemplate = $this->paymentFrameFactory->getPaymentFrame($paymentId);

        if (!$mappedFrameTemplate) {
            return;
        }

        $shopName = $this->systemConfigReader->get(
            'core.basicInformation.shopName',
            $event->getSalesChannelContext()->getSalesChannel()->getId()
        );

        $event->getPage()->addExtension(
            PaymentFramePageExtension::EXTENSION_NAME,
            (new PaymentFramePageExtension())
                ->setPaymentFrame($mappedFrameTemplate)
                ->setShopName(\is_string($shopName) ? $shopName : '')
        );
    }

    private function addCreditCardExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $creditCards = $this->deviceRepository->getCollectionByCustomer($customer, $event->getContext(), UnzerPaymentDeviceEntity::DEVICE_TYPE_CREDIT_CARD);
        $extension = new CreditCardPageExtension();

        /** @var UnzerPaymentDeviceEntity $creditCard */
        foreach ($creditCards as $creditCard) {
            $extension->addCreditCard($creditCard);
        }

        $event->getPage()->addExtension(CreditCardPageExtension::EXTENSION_NAME, $extension);
    }

    private function addPayPalExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $payPalAccounts = $this->deviceRepository->getCollectionByCustomer($customer, $event->getContext(), UnzerPaymentDeviceEntity::DEVICE_TYPE_PAYPAL);
        $extension = new PayPalPageExtension();

        /** @var UnzerPaymentDeviceEntity $payPalAccount */
        foreach ($payPalAccounts as $payPalAccount) {
            $extension->addPayPalAccount($payPalAccount);
        }

        $extension->setPublicConfig([
            'paypalShowSaveAccount' => $this->configData->get(ConfigReader::CONFIG_KEY_PAYPAL_SHOW_SAVE_ACCOUNT),
        ]);

        if ($event->getRequest()->getSession()->get(ExpressCheckoutService::SESSION_PAYPAL_PAYMENT_ID)) {
            $extension->setPayPalAccounts([]);
            $extension->setPublicConfig([
                'paypalShowSaveAccount' => false,
            ]);
        }

        $event->getPage()->addExtension(PayPalPageExtension::EXTENSION_NAME, $extension);
    }

    private function addDirectDebitExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $directDebitDevices = $this->deviceRepository->getCollectionByCustomer($customer, $event->getContext(), UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT);
        $extension = new DirectDebitPageExtension();

        /** @var UnzerPaymentDeviceEntity $directDebitDevice */
        foreach ($directDebitDevices as $directDebitDevice) {
            $extension->addDirectDebitDevice($directDebitDevice);
        }

        $event->getPage()->addExtension(DirectDebitPageExtension::EXTENSION_NAME, $extension);
    }

    private function addDirectDebitSecuredExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $directDebitDevices = $this->deviceRepository->getCollectionByCustomer($customer, $event->getContext(), UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT_SECURED);
        $extension = (new DirectDebitSecuredPageExtension())->setDisplayDirectDebitDeviceSelection(true);

        /** @var UnzerPaymentDeviceEntity $directDebitDevice */
        foreach ($directDebitDevices as $directDebitDevice) {
            $extension->addDirectDebitDevice($directDebitDevice);
        }

        $event->getPage()->addExtension(DirectDebitSecuredPageExtension::EXTENSION_NAME, $extension);
    }

    private function addInstallmentSecuredExtension(PageLoadedEvent $event): void
    {
        $extension = new InstallmentSecuredPageExtension();
        $extension->setCurrency($event->getSalesChannelContext()->getCurrency()->getIsoCode());

        if ($event instanceof CheckoutConfirmPageLoadedEvent) {
            $extension->setAmount($event->getPage()->getCart()->getPrice()->getTotalPrice());
        } elseif ($event instanceof AccountEditOrderPageLoadedEvent) {
            $extension->setAmount($event->getPage()->getOrder()->getPrice()->getTotalPrice());
        }
        $extension->setOrderDate(date('Y-m-d'));

        $event->getPage()->addExtension(InstallmentSecuredPageExtension::EXTENSION_NAME, $extension);
    }

    private function addApplePayV2Extension(PageLoadedEvent $event): void
    {
        $extension = $this->extensionFactory->getApplePayExtension($event->getSalesChannelContext()->getSalesChannelId());
        $publicConfig = $extension->getPublicConfig();
        $publicConfig['paymentTypeId'] = $event->getRequest()->getSession()->get(ExpressCheckoutService::SESSION_APPLEPAY_PAYMENT_TYPE_ID);
        $extension->setPublicConfig($publicConfig);
        $event->getPage()->addExtension(ApplePayV2PageExtension::EXTENSION_NAME, $extension);
    }

    private function addGooglePayExtension(PageLoadedEvent $event): void
    {
        $extension = $this->extensionFactory->getGooglePayExtension($event->getSalesChannelContext()->getSalesChannelId());
        $publicConfig = $extension->getPublicConfig();
        $publicConfig['paymentTypeId'] = $event->getRequest()->getSession()->get(ExpressCheckoutService::SESSION_GOOGLE_PAYMENT_TYPE_ID);
        $extension->setPublicConfig($publicConfig);
        $event->getPage()->addExtension(GooglePayPageExtension::EXTENSION_NAME, $extension);
    }

    private function addPaylaterInstallmentExtension(PageLoadedEvent $event): void
    {
        $extension = new PaylaterInstallmentPageExtension();
        $extension->setCurrency($event->getSalesChannelContext()->getCurrency()->getIsoCode());

        if ($event instanceof CheckoutConfirmPageLoadedEvent) {
            $extension->setAmount($event->getPage()->getCart()->getPrice()->getTotalPrice());
        } elseif ($event instanceof AccountEditOrderPageLoadedEvent) {
            $extension->setAmount($event->getPage()->getOrder()->getPrice()->getTotalPrice());
        }

        $event->getPage()->addExtension(PaylaterInstallmentPageExtension::EXTENSION_NAME, $extension);
    }

    private function addPaylaterDirectDebitSecuredExtension(PageLoadedEvent $event): void
    {
        $extension = new PaylaterDirectDebitSecuredPageExtension();
        $extension->setCurrency($event->getSalesChannelContext()->getCurrency()->getIsoCode());

        if ($event instanceof CheckoutConfirmPageLoadedEvent) {
            $extension->setAmount($event->getPage()->getCart()->getPrice()->getTotalPrice());
        } elseif ($event instanceof AccountEditOrderPageLoadedEvent) {
            $extension->setAmount($event->getPage()->getOrder()->getPrice()->getTotalPrice());
        }

        $event->getPage()->addExtension(PaylaterDirectDebitSecuredPageExtension::EXTENSION_NAME, $extension);
    }

    private function getLocaleByLanguageId(string $languageId, Context $context): string
    {
        $criteria = new Criteria([$languageId]);
        $criteria->addAssociation('locale');

        /** @var LanguageEntity|null $searchResult */
        $searchResult = $this->languageRepository->search($criteria, $context)->first();

        if ($searchResult === null || $searchResult->getLocale() === null) {
            return ClientFactoryInterface::DEFAULT_LOCALE;
        }

        return $searchResult->getLocale()->getCode();
    }

    private function getPublicKey(SalesChannelContext $salesChannelContext): string
    {
        $keyPairContext = KeyPairContext::createFromSalesChannelContext($salesChannelContext);

        if (!$keyPairContext) {
            return $this->configData->get(ConfigReader::CONFIG_KEY_PUBLIC_KEY);
        }

        return $this->keyPairConfigReader->getPublicKey($keyPairContext);
    }
}
