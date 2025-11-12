<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\Page;

use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use UnzerPayment6\Components\ExpressCheckout\ExpressCheckoutService;

class NavigationPageEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            NavigationPageLoadedEvent::class => 'onNavPageLoaded',
        ];
    }

    public function onNavPageLoaded(NavigationPageLoadedEvent $event): void
    {
        $request = $event->getRequest();
        if (($request->attributes->get('_route') ?? null) !== 'frontend.home.page') {
            return;
        }

        if (!$request->hasSession()) {
            return;
        }

        $this->unsetExpressData($request);
    }

    private function unsetExpressData(Request $request): void
    {
        try {
            $session = $request->getSession();
            $session->remove(ExpressCheckoutService::SESSION_APPLEPAY_PAYMENT_TYPE_ID);
            $session->remove(ExpressCheckoutService::SESSION_GOOGLE_PAYMENT_TYPE_ID);
            $session->remove(ExpressCheckoutService::SESSION_PAYPAL_PAYMENT_ID);
            $session->remove(ExpressCheckoutService::SESSION_PAYPAL_PAYMENT_TYPE_ID);
            $session->remove(ExpressCheckoutService::SESSION_SELECTED_EXPRESS_METHOD);
        } catch (\Throwable $exception) {
            // not worth handling
        }
    }
}
