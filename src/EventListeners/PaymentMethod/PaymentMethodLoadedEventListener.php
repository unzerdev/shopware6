<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\PaymentMethod;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntityIdSearchResultLoadedEvent;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntitySearchResultLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerPayment6\UnzerPayment6;

class PaymentMethodLoadedEventListener implements EventSubscriberInterface
{
    /** @var CartService */
    private $cartService;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(CartService $cartService, ConfigReaderInterface $configReader)
    {
        $this->cartService  = $cartService;
        $this->configReader = $configReader;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.payment_method.search.id.result.loaded' => ['onSalesChannelIdSearchResultLoaded', -1],
            'sales_channel.payment_method.search.result.loaded'    => ['onSalesChannelSearchResultLoaded', -1],
        ];
    }

    public function onSalesChannelIdSearchResultLoaded(SalesChannelEntityIdSearchResultLoadedEvent $event): void
    {
        $result                   = $event->getResult();
        $salesChannelContext      = $event->getSalesChannelContext();
        $salesChannelContextToken = $event->getSalesChannelContext()->getToken();
        $cart                     = $this->cartService->getCart($salesChannelContextToken, $salesChannelContext);
        $configData               = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        if ($this->isZeroAmountCart($cart, $salesChannelContext->getCurrency())) {
            $this->removePaymentMethodsFromIdResult($result);
        }

        if (!$this->isConfigurationValid($salesChannelContext->getSalesChannel()->getId())) {
            $this->removePaymentMethodsFromIdResult($result);
        }
    }

    public function onSalesChannelSearchResultLoaded(SalesChannelEntitySearchResultLoadedEvent $event): void
    {
        $result                   = $event->getResult();
        $salesChannelContext      = $event->getSalesChannelContext();
        $salesChannelContextToken = $event->getSalesChannelContext()->getToken();
        $cart                     = $this->cartService->getCart($salesChannelContextToken, $salesChannelContext);

        if ($this->isZeroAmountCart($cart, $salesChannelContext->getCurrency())) {
            $this->removePaymentMethodsFromResult($result);
        }

        if (!$this->isConfigurationValid($salesChannelContext->getSalesChannel()->getId())) {
            $this->removePaymentMethodsFromResult($result);
        }
    }

    protected function removePaymentMethodsFromIdResult(IdSearchResult $result): void
    {
        $filteredPaymentMethods = array_filter($result->getIds(), static function (string $paymentMethod) {
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

    protected function isZeroAmountCart(Cart $cart, CurrencyEntity $currency): bool
    {
        $totalAmount        = $cart->getPrice()->getTotalPrice();
        $currencyPrecision  = min($currency->getDecimalPrecision(), UnzerPayment6::MAX_DECIMAL_PRECISION);
        $roundedAmountTotal = (int) round($totalAmount, $currencyPrecision);

        return $roundedAmountTotal <= 0;
    }
}
