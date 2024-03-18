<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\PaymentMethod;

use Shopware\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntityIdSearchResultLoadedEvent;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntitySearchResultLoadedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerPayment6\UnzerPayment6;

class PaymentMethodLoadedEventListener implements EventSubscriberInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(ConfigReaderInterface $configReader)
    {
        $this->configReader = $configReader;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.payment_method.search.id.result.loaded' => ['onSalesChannelIdSearchResultLoaded', -1],
            'sales_channel.payment_method.search.result.loaded'    => ['onSalesChannelSearchResultLoaded', -1],
            AccountEditOrderPageLoadedEvent::class                 => 'onAccountEditOrderPageLoaded',
            CheckoutConfirmPageLoadedEvent::class                  => 'onCheckoutConfirmPageLoaded',
        ];
    }

    public function onSalesChannelIdSearchResultLoaded(SalesChannelEntityIdSearchResultLoadedEvent $event): void
    {
        $result              = $event->getResult();
        $salesChannelContext = $event->getSalesChannelContext();

        if (!$this->isConfigurationValid($salesChannelContext->getSalesChannel()->getId())) {
            $this->removePaymentMethodsFromIdResult($result, PaymentInstaller::PAYMENT_METHOD_IDS);

            return;
        }

        $blockedPaymentMethods = $this->getBlockedPaymentMethods($salesChannelContext);

        if ($blockedPaymentMethods === []) {
            return;
        }

        $this->removePaymentMethodsFromIdResult($result, $blockedPaymentMethods);
    }

    public function onSalesChannelSearchResultLoaded(SalesChannelEntitySearchResultLoadedEvent $event): void
    {
        $result              = $event->getResult();
        $salesChannelContext = $event->getSalesChannelContext();

        if (!$this->isConfigurationValid($salesChannelContext->getSalesChannel()->getId())) {
            $this->removePaymentMethodsFromResult($result, PaymentInstaller::PAYMENT_METHOD_IDS);

            return;
        }

        $blockedPaymentMethods = $this->getBlockedPaymentMethods($salesChannelContext);

        if ($blockedPaymentMethods === []) {
            return;
        }

        $this->removePaymentMethodsFromResult($result, $blockedPaymentMethods);
    }

    public function onAccountEditOrderPageLoaded(AccountEditOrderPageLoadedEvent $pageLoadedEvent): void
    {
        $page        = $pageLoadedEvent->getPage();
        $order       = $page->getOrder();
        $totalAmount = $order->getAmountTotal();

        if ($this->isZeroAmount($totalAmount, $pageLoadedEvent->getSalesChannelContext()->getCurrency())) {
            $page->setPaymentMethods($page->getPaymentMethods()->filter(static function (PaymentMethodEntity $paymentMethod) {
                return !in_array($paymentMethod->getId(), PaymentInstaller::PAYMENT_METHOD_IDS, true);
            })
            );
            $pageLoadedEvent->getSalesChannelContext()->assign(['paymentMethods' => $page->getPaymentMethods()]);
        }
    }

    public function onCheckoutConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $pageLoadedEvent): void
    {
        $salesChannelContext = $pageLoadedEvent->getSalesChannelContext();
        $page                = $pageLoadedEvent->getPage();
        $cart                = $page->getCart();
        $totalAmount         = $cart->getPrice()->getTotalPrice();

        if ($this->isZeroAmount($totalAmount, $salesChannelContext->getCurrency())) {
            $page->setPaymentMethods($page->getPaymentMethods()->filter(static function (PaymentMethodEntity $paymentMethod) {
                return !in_array($paymentMethod->getId(), PaymentInstaller::PAYMENT_METHOD_IDS, true);
            })
            );

            $salesChannelContext->assign(['paymentMethods' => $page->getPaymentMethods()]);
        }

        if (in_array($salesChannelContext->getPaymentMethod()->getId(), PaymentInstaller::PAYMENT_METHOD_IDS, true)
            && !array_key_exists($salesChannelContext->getPaymentMethod()->getId(), $page->getPaymentMethods()->getElements())) {
            $page->getCart()->addErrors(new PaymentMethodBlockedError($salesChannelContext->getPaymentMethod()->getName() ?? 'unknown'));
        }
    }

    protected function removePaymentMethodsFromIdResult(IdSearchResult $result, array $paymentIdsToBeRemoved): void
    {
        $filteredPaymentMethods = array_filter($result->getIds(), static function ($paymentMethod) use ($paymentIdsToBeRemoved) {
            return !in_array($paymentMethod, $paymentIdsToBeRemoved, true);
        });

        $result->assign([
            'total'    => count($filteredPaymentMethods),
            'ids'      => $filteredPaymentMethods,
            'entities' => $filteredPaymentMethods,
            'elements' => $filteredPaymentMethods,
        ]);
    }

    protected function removePaymentMethodsFromResult(EntitySearchResult $result, array $paymentIdsToBeRemoved): void
    {
        $filteredResult = $result->getEntities()->filter(static function (PaymentMethodEntity $entity) use ($paymentIdsToBeRemoved) {
            return !in_array($entity->getId(), $paymentIdsToBeRemoved, true);
        });

        $result->assign([
            'total'    => count($filteredResult),
            'entities' => $filteredResult,
            'elements' => $filteredResult->getElements(),
        ]);
    }

    protected function isConfigurationValid(string $salesChannelId): bool
    {
        $configData = $this->configReader->read($salesChannelId);

        return !(empty($configData->get(ConfigReader::CONFIG_KEY_PUBLIC_KEY, '')) || empty($configData->get(ConfigReader::CONFIG_KEY_PRIVATE_KEY, '')));
    }

    protected function isZeroAmount(float $totalAmount, CurrencyEntity $currency): bool
    {
        $currencyPrecision  = min($currency->getItemRounding()->getDecimals(), UnzerPayment6::MAX_DECIMAL_PRECISION);
        $roundedAmountTotal = (int) round($totalAmount * (10 ** $currencyPrecision));

        return $roundedAmountTotal <= 0;
    }

    protected function getBlockedPaymentMethods(SalesChannelContext $salesChannelContext): array
    {
        $paymentMethodIdsToBeRemoved = [];

        if ($salesChannelContext->getCurrency()->getIsoCode() !== 'EUR') {
            $paymentMethodIdsToBeRemoved[] = PaymentInstaller::PAYMENT_ID_PAYLATER_DIRECT_DEBIT_SECURED;
        }

        $customer = $salesChannelContext->getCustomer();

        if ($customer === null) {
            return $paymentMethodIdsToBeRemoved;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if ($billingAddress === null) {
            return $paymentMethodIdsToBeRemoved;
        }

        $invoiceCountry = $billingAddress->getCountry();

        if ($invoiceCountry !== null && $invoiceCountry->getIso() !== 'DE' && $invoiceCountry->getIso() !== 'AT') {
            $paymentMethodIdsToBeRemoved[] = PaymentInstaller::PAYMENT_ID_PAYLATER_DIRECT_DEBIT_SECURED;
        }

        return $paymentMethodIdsToBeRemoved;
    }
}
