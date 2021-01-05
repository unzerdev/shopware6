<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\PaymentMethod;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntityIdSearchResultLoadedEvent;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntitySearchResultLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerPayment6\UnzerPayment6;

class PaymentMethodLoadedEventListener implements EventSubscriberInterface
{
    /** @var CartService */
    private $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
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

        if ($this->isZeroAmountCart($cart, $salesChannelContext->getCurrency())) {
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
    }

    public function onSalesChannelSearchResultLoaded(SalesChannelEntitySearchResultLoadedEvent $event): void
    {
        $result                   = $event->getResult();
        $salesChannelContext      = $event->getSalesChannelContext();
        $salesChannelContextToken = $event->getSalesChannelContext()->getToken();
        $cart                     = $this->cartService->getCart($salesChannelContextToken, $salesChannelContext);

        if ($this->isZeroAmountCart($cart, $salesChannelContext->getCurrency())) {
            $filteredResult = $result->getEntities()->filter(static function (PaymentMethodEntity $entity) {
                return !in_array($entity->getId(), PaymentInstaller::PAYMENT_METHOD_IDS, true);
            });

            $result->assign([
                'total'    => count($filteredResult),
                'entities' => $filteredResult,
                'elements' => $filteredResult->getElements(),
            ]);
        }
    }

    protected function isZeroAmountCart(Cart $cart, CurrencyEntity $currency): bool
    {
        $totalAmount        = $cart->getPrice()->getTotalPrice();
        $currencyPrecision  = min($currency->getDecimalPrecision(), UnzerPayment6::MAX_DECIMAL_PRECISION);
        $roundedAmountTotal = (int) round($totalAmount, $currencyPrecision);

        return $roundedAmountTotal <= 0;
    }
}
