<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\ExpressButtons;

use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\Storefront\ExtensionFactory;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\ApplePayV2PageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\GooglePayPageExtension;

class ExpressButtonsEventListener implements EventSubscriberInterface
{
    public function __construct(
        private ConfigReaderInterface $configReader,
        private ExtensionFactory $extensionFactory,
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
        $config = $this->configReader->read($event->getSalesChannelContext()->getSalesChannel()->getId());

        if (!$config->get(ConfigReader::CONFIG_KEY_USE_EXPRESS_PAYPAL)
           && !$config->get(ConfigReader::CONFIG_KEY_USE_EXPRESS_GOOGLE)
           && !$config->get(ConfigReader::CONFIG_KEY_USE_EXPRESS_APPLEPAY)) {
            return;
        }

        $event->getPage()->addExtension('UnzerExpressButtons', new ArrayStruct([
            'publicKey' => $config->get(ConfigReader::CONFIG_KEY_PUBLIC_KEY),
            'usePaypal' => $config->get(ConfigReader::CONFIG_KEY_USE_EXPRESS_PAYPAL),
            'useGooglePay' => $config->get(ConfigReader::CONFIG_KEY_USE_EXPRESS_GOOGLE),
            'useApplePay' => $config->get(ConfigReader::CONFIG_KEY_USE_EXPRESS_APPLEPAY),
        ]));
        $googlePayExtension = $this->extensionFactory->getGooglePayExtension($event->getSalesChannelContext()->getSalesChannelId());
        $event->getPage()->addExtension(GooglePayPageExtension::EXTENSION_NAME, $googlePayExtension);

        $applePayExtension = $this->extensionFactory->getApplePayExtension($event->getSalesChannelContext()->getSalesChannelId());
        $event->getPage()->addExtension(ApplePayV2PageExtension::EXTENSION_NAME, $applePayExtension);
    }
}
