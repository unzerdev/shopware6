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
            $this->removePaymentMethodsFromIdResult($result);
        }
    }

    public function onSalesChannelSearchResultLoaded(SalesChannelEntitySearchResultLoadedEvent $event): void
    {
        $result              = $event->getResult();
        $salesChannelContext = $event->getSalesChannelContext();

        if (!$this->isConfigurationValid($salesChannelContext->getSalesChannel()->getId())) {
            $this->removePaymentMethodsFromResult($result);
        }
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

    protected function removePaymentMethodsFromIdResult(IdSearchResult $result): void
    {
        $filteredPaymentMethods = array_filter($result->getIds(), static function ($paymentMethod) {
            return !in_array($paymentMethod, PaymentInstaller::PAYMENT_METHOD_IDS, true);
        });

        $result->assign([
            'total'    => count($filteredPaymentMethods),
            'ids'      => $filteredPaymentMethods,
            'entities' => $filteredPaymentMethods,
            'elements' => $filteredPaymentMethods,
        ]);
    }

    protected function removePaymentMethodsFromResult(EntitySearchResult $result): void
    {
        $filteredResult = $result->getEntities()->filter(static function (PaymentMethodEntity $entity) {
            return !in_array($entity->getId(), PaymentInstaller::PAYMENT_METHOD_IDS, true);
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
}
