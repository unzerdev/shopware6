<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\ExpressButtons;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\Storefront\ExtensionFactory;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\ApplePayV2PageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\GooglePayPageExtension;
use UnzerPayment6\Components\UnzerUtil\UnzerApiUtil;
use UnzerPayment6\EventListeners\Traits\HasLocaleTrait;
use UnzerPayment6\Installer\PaymentInstaller;

readonly class ExpressButtonsEventListener implements EventSubscriberInterface
{
    use HasLocaleTrait;

    public function __construct(
        private ConfigReaderInterface $configReader,
        private ExtensionFactory $extensionFactory,
        private EntityRepository $salesChannelRepository,
        private UnzerApiUtil $unzerApiUtil,
        private EntityRepository $languageRepository
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            CheckoutCartPageLoadedEvent::class => 'addExpressButtons',
            OffcanvasCartPageLoadedEvent::class => 'addExpressButtons',
        ];
    }

    public function addExpressButtons(PageLoadedEvent $event): void
    {
        $context = $event->getSalesChannelContext()->getContext();
        $config = $this->configReader->read($event->getSalesChannelContext()->getSalesChannel()->getId());

        if (!$config->get(ConfigReader::CONFIG_KEY_USE_EXPRESS_PAYPAL)
           && !$config->get(ConfigReader::CONFIG_KEY_USE_EXPRESS_GOOGLE)
           && !$config->get(ConfigReader::CONFIG_KEY_USE_EXPRESS_APPLEPAY)) {
            return;
        }

        $event->getPage()->addExtension('UnzerExpressButtons', new ArrayStruct([
            'publicKey' => $config->get(ConfigReader::CONFIG_KEY_PUBLIC_KEY),
            'keyPairConfig' => $this->unzerApiUtil->getCachedKeypairConfig($config->get(ConfigReader::CONFIG_KEY_PUBLIC_KEY)),
            'locale' => $this->getLocaleByLanguageId($context->getLanguageId(), $context),
            'usePaypal' => $config->get(ConfigReader::CONFIG_KEY_USE_EXPRESS_PAYPAL) && $this->isPaymentMethodActive(PaymentInstaller::PAYMENT_ID_PAYPAL, $event->getSalesChannelContext()),
            'useGooglePay' => $config->get(ConfigReader::CONFIG_KEY_USE_EXPRESS_GOOGLE) && $this->isPaymentMethodActive(PaymentInstaller::PAYMENT_ID_GOOGLE_PAY, $event->getSalesChannelContext()),
            'useApplePay' => $config->get(ConfigReader::CONFIG_KEY_USE_EXPRESS_APPLEPAY) && $this->isPaymentMethodActive(PaymentInstaller::PAYMENT_ID_APPLE_PAY_V2, $event->getSalesChannelContext()),
        ]));
        $googlePayExtension = $this->extensionFactory->getGooglePayExtension($event->getSalesChannelContext()->getSalesChannelId());
        $event->getPage()->addExtension(GooglePayPageExtension::EXTENSION_NAME, $googlePayExtension);

        $applePayExtension = $this->extensionFactory->getApplePayExtension($event->getSalesChannelContext()->getSalesChannelId());
        $event->getPage()->addExtension(ApplePayV2PageExtension::EXTENSION_NAME, $applePayExtension);
    }

    public function isPaymentMethodActive(string $paymentMethodId, SalesChannelContext $context): bool
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_AND,
                [
                    new EqualsFilter('id', $context->getSalesChannel()->getId()),
                    new EqualsFilter('paymentMethods.id', $paymentMethodId),
                    new EqualsFilter('paymentMethods.active', true),
                ]
            )
        );

        return $this->salesChannelRepository->searchIds($criteria, $context->getContext())->firstId() !== null;
    }
}
