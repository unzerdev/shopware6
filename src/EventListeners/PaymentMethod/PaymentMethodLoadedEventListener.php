<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\PaymentMethod;

use Shopware\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Shopware\Core\Checkout\Payment\Event\PaymentMethodRouteCacheKeyEvent;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntityIdSearchResultLoadedEvent;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntitySearchResultLoadedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\ExpressCheckout\ExpressCheckoutService;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerPayment6\UnzerPayment6;

readonly class PaymentMethodLoadedEventListener implements EventSubscriberInterface
{
    public function __construct(
        private ConfigReaderInterface $configReader,
        private RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.payment_method.search.id.result.loaded' => ['onSalesChannelIdSearchResultLoaded', -1],
            'sales_channel.payment_method.search.result.loaded' => ['onSalesChannelSearchResultLoaded', -1],
            'payment_method.search.result.loaded' => ['onSearchResultLoaded', -1],
            AccountEditOrderPageLoadedEvent::class => 'onAccountEditOrderPageLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmPageLoaded',
            PaymentMethodRouteCacheKeyEvent::class => 'addCacheKeyParts',
        ];
    }

    public function onSearchResultLoaded(EntitySearchResultLoadedEvent $event): void
    {
        foreach (PaymentInstaller::REMOVED_PAYMENT_METHOD_IDS as $removedPaymentMethodId) {
            try {
                $event->getResult()->remove($removedPaymentMethodId);
            } catch (\Exception $e) {
                // no worries
            }
        }
    }

    public function onSalesChannelIdSearchResultLoaded(SalesChannelEntityIdSearchResultLoadedEvent $event): void
    {
        $result = $event->getResult();
        $salesChannelContext = $event->getSalesChannelContext();

        if (!$this->isConfigurationValid($salesChannelContext->getSalesChannel()->getId())) {
            $this->removePaymentMethodsFromIdResult($result, PaymentInstaller::PAYMENT_METHOD_IDS);

            return;
        }

        $blockedPaymentMethods = $this->getBlockedPaymentMethods($salesChannelContext);
        $blockedPaymentMethods = array_merge($blockedPaymentMethods, $this->getUnselectedExpressPaymentMethods($result));

        if ($blockedPaymentMethods === []) {
            return;
        }

        $this->removePaymentMethodsFromIdResult($result, $blockedPaymentMethods);
    }

    public function onSalesChannelSearchResultLoaded(SalesChannelEntitySearchResultLoadedEvent $event): void
    {
        $result = $event->getResult();
        $salesChannelContext = $event->getSalesChannelContext();

        if (!$this->isConfigurationValid($salesChannelContext->getSalesChannel()->getId())) {
            $this->removePaymentMethodsFromResult($result, PaymentInstaller::PAYMENT_METHOD_IDS);

            return;
        }

        $blockedPaymentMethods = $this->getBlockedPaymentMethods($salesChannelContext);
        $blockedPaymentMethods = array_merge($blockedPaymentMethods, $this->getUnselectedExpressPaymentMethods($result));

        if ($blockedPaymentMethods === []) {
            return;
        }

        $this->removePaymentMethodsFromResult($result, $blockedPaymentMethods);
    }

    public function onAccountEditOrderPageLoaded(AccountEditOrderPageLoadedEvent $pageLoadedEvent): void
    {
        $page = $pageLoadedEvent->getPage();
        $order = $page->getOrder();
        $totalAmount = $order->getAmountTotal();

        if ($this->isZeroAmount($totalAmount, $pageLoadedEvent->getSalesChannelContext()->getCurrency())) {
            $page->setPaymentMethods(
                $page->getPaymentMethods()->filter(static function (PaymentMethodEntity $paymentMethod) {
                    return !\in_array($paymentMethod->getId(), PaymentInstaller::PAYMENT_METHOD_IDS, true);
                })
            );
            $pageLoadedEvent->getSalesChannelContext()->assign(['paymentMethods' => $page->getPaymentMethods()]);
        }
    }

    public function onCheckoutConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $pageLoadedEvent): void
    {
        $salesChannelContext = $pageLoadedEvent->getSalesChannelContext();
        $page = $pageLoadedEvent->getPage();
        $cart = $page->getCart();
        $totalAmount = $cart->getPrice()->getTotalPrice();

        if ($this->isZeroAmount($totalAmount, $salesChannelContext->getCurrency())) {
            $page->setPaymentMethods(
                $page->getPaymentMethods()->filter(static function (PaymentMethodEntity $paymentMethod) {
                    return !\in_array($paymentMethod->getId(), PaymentInstaller::PAYMENT_METHOD_IDS, true);
                })
            );

            $salesChannelContext->assign(['paymentMethods' => $page->getPaymentMethods()]);
        }

        if (\in_array($salesChannelContext->getPaymentMethod()->getId(), PaymentInstaller::PAYMENT_METHOD_IDS, true)
            && !\array_key_exists($salesChannelContext->getPaymentMethod()->getId(), $page->getPaymentMethods()->getElements())) {
            $page->getCart()->addErrors(new PaymentMethodBlockedError($salesChannelContext->getPaymentMethod()->getName() ?? 'unknown'));
        }
    }

    public function addCacheKeyParts(PaymentMethodRouteCacheKeyEvent $event): void
    {
        $salesChannelContext = $event->getContext();
        $event->addPart('BlockedPaymentMethodsCurrency_' . $salesChannelContext->getCurrency()->getIsoCode());

        $invoiceIso = $salesChannelContext
            ->getCustomer()?->getActiveBillingAddress()?->getCountry()?->getIso();

        if (!empty($invoiceIso)) {
            $event->addPart('BlockedPaymentMethodsInvoiceCtry_' . $invoiceIso);
        }

        $customerCompany = $salesChannelContext->getCustomer()?->getActiveBillingAddress()?->getCompany();

        if (!empty($customerCompany)) {
            $event->addPart('BlockedPaymentMethodsB2B');
        }

        if (!$event->getRequest()->hasSession()) {
            return;
        }

        $isExpress = $event->getRequest()->query->getBoolean('isExpressCheckout', false) ?? false;
        if (!$isExpress) {
            return;
        }

        $session = $event->getRequest()->getSession();
        $expressPaymentMethodId = $session->get(ExpressCheckoutService::SESSION_SELECTED_EXPRESS_METHOD);

        $event->addPart('unzerExpressActive_' . $expressPaymentMethodId);
    }

    protected function removePaymentMethodsFromIdResult(IdSearchResult $result, array $paymentIdsToBeRemoved): void
    {
        $filteredPaymentMethods = array_filter($result->getIds(), static function ($paymentMethod) use ($paymentIdsToBeRemoved) {
            return !\in_array($paymentMethod, $paymentIdsToBeRemoved, true);
        });

        $result->assign([
            'total' => \count($filteredPaymentMethods),
            'ids' => $filteredPaymentMethods,
            'entities' => $filteredPaymentMethods,
            'elements' => $filteredPaymentMethods,
        ]);
    }

    protected function removePaymentMethodsFromResult(EntitySearchResult $result, array $paymentIdsToBeRemoved): void
    {
        $filteredResult = $result->getEntities()->filter(static function (PaymentMethodEntity $entity) use ($paymentIdsToBeRemoved) {
            return !\in_array($entity->getId(), $paymentIdsToBeRemoved, true);
        });

        $result->assign([
            'total' => \count($filteredResult),
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
        $currencyPrecision = min($currency->getItemRounding()->getDecimals(), UnzerPayment6::MAX_DECIMAL_PRECISION);
        $roundedAmountTotal = (int) round($totalAmount * (10 ** $currencyPrecision));

        return $roundedAmountTotal <= 0;
    }

    protected function getBlockedPaymentMethods(SalesChannelContext $salesChannelContext): array
    {
        $paymentMethodIdsToBeRemoved = PaymentInstaller::REMOVED_PAYMENT_METHOD_IDS;

        if ($salesChannelContext->getCurrency()->getIsoCode() !== 'EUR') {
            $paymentMethodIdsToBeRemoved[] = PaymentInstaller::PAYMENT_ID_PAYLATER_DIRECT_DEBIT_SECURED;
            $paymentMethodIdsToBeRemoved[] = PaymentInstaller::PAYMENT_ID_WERO;
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
        if ($invoiceCountry !== null && $invoiceCountry->getIso() !== 'DE') {
            $paymentMethodIdsToBeRemoved[] = PaymentInstaller::PAYMENT_ID_WERO;
        }

        if (!empty($customer->getActiveBillingAddress()?->getCompany())) {
            $paymentMethodIdsToBeRemoved[] = PaymentInstaller::PAYMENT_ID_PAYLATER_DIRECT_DEBIT_SECURED;
            $paymentMethodIdsToBeRemoved[] = PaymentInstaller::PAYMENT_ID_PAYLATER_INSTALLMENT;
        }

        return $paymentMethodIdsToBeRemoved;
    }

    protected function getUnselectedExpressPaymentMethods(EntitySearchResult $result): array
    {
        $unselectedIds = [];
        $request = $this->requestStack->getCurrentRequest();
        $isExpress = $request?->query->getBoolean('isExpressCheckout', false) ?? false;
        if ($isExpress) {
            $session = $request && $request->hasSession() ? $request->getSession() : null;
            $expressPaymentMethodId = $session->get(ExpressCheckoutService::SESSION_SELECTED_EXPRESS_METHOD);
            foreach ($result->getIds() as $id) {
                if ($id !== $expressPaymentMethodId) {
                    $unselectedIds[] = $id;
                }
            }
        }

        return $unselectedIds;
    }
}
