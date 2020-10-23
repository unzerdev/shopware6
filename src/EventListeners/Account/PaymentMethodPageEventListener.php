<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\Account;

use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\Struct\PageExtension\Account\PaymentMethodPageExtension;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;

class PaymentMethodPageEventListener implements EventSubscriberInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var UnzerPaymentDeviceRepositoryInterface */
    private $deviceRepository;

    public function __construct(ConfigReaderInterface $configReader, UnzerPaymentDeviceRepositoryInterface $deviceRepository)
    {
        $this->configReader     = $configReader;
        $this->deviceRepository = $deviceRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AccountPaymentMethodPageLoadedEvent::class => 'onLoadAccountPaymentMethod',
        ];
    }

    public function onLoadAccountPaymentMethod(AccountPaymentMethodPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();

        if (!$salesChannelContext->getCustomer()) {
            return;
        }

        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();

        $registerCreditCards = $this->configReader->read($salesChannelId)->get(ConfigReader::CONFIG_KEY_REGISTER_CARD, false);
        $registerPayPal      = $this->configReader->read($salesChannelId)->get(ConfigReader::CONFIG_KEY_REGISTER_PAYPAL, false);
        $registerDirectDebit = $this->configReader->read($salesChannelId)->get(ConfigReader::CONFIG_KEY_REGISTER_DIRECT_DEBIT, false);
        $extension           = new PaymentMethodPageExtension();
        $devices             = $this->deviceRepository->getCollectionByCustomer($salesChannelContext->getCustomer(), $salesChannelContext->getContext());
        $extension->setDeviceRemoved((bool) $event->getRequest()->get('deviceRemoved'));

        if ($registerCreditCards && $salesChannelContext->getCustomer() !== null) {
            $creditCards = $devices->filterByProperty('deviceType', UnzerPaymentDeviceEntity::DEVICE_TYPE_CREDIT_CARD)->getElements();

            $extension->addPaymentDevices($creditCards);
        }

        if ($registerPayPal && $salesChannelContext->getCustomer() !== null) {
            $payPalAccounts = $devices->filterByProperty('deviceType', UnzerPaymentDeviceEntity::DEVICE_TYPE_PAYPAL)->getElements();

            $extension->addPaymentDevices($payPalAccounts);
        }

        if ($registerDirectDebit && $salesChannelContext->getCustomer() !== null) {
            $directDebitDevices           = $devices->filterByProperty('deviceType', UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT)->getElements();
            $directDebitGuaranteedDevices = $devices->filterByProperty('deviceType', UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT_GUARANTEED)->getElements();

            $extension->addPaymentDevices($directDebitDevices);
            $extension->addPaymentDevices($directDebitGuaranteedDevices);
        }

        $event->getPage()->addExtension('unzer', $extension);
    }
}
