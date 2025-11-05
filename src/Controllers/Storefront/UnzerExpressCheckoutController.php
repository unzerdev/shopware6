<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Storefront;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ClientFactory\ClientFactory;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\ExpressCheckout\ExpressCheckoutService;
use UnzerPayment6\Components\ResourceHydrator\MetadataResourceHydrator;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class UnzerExpressCheckoutController extends StorefrontController
{
    public function __construct(
        private readonly ExpressCheckoutService $expressCheckoutService,
        private readonly MetadataResourceHydrator $metadataResourceHydrator,
        protected readonly ConfigReaderInterface $configReader,
        protected readonly LoggerInterface $logger,
        protected readonly ClientFactory $clientFactory,
    ) {
    }

    #[Route(path: '/unzer/paypal-express', name: 'frontend.unzer.paypal-express', defaults: ['csrf_protected' => false], methods: ['POST', 'GET'])]
    public function paypalExpress(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $paymentTypeId = $request->get('paymentTypeId');
        $client = $this->clientFactory->createClientFromSalesChannelId($salesChannelContext->getSalesChannelId(), $request);

        $shopwareCart = $this->expressCheckoutService->getCart($salesChannelContext);
        $basket = (new Basket())
            ->setTotalValueGross($shopwareCart->getPrice()->getTotalPrice())
            ->setCurrencyCode($salesChannelContext->getCurrency()->getIsoCode());
        $basketItem = (new BasketItem())
            ->setAmountPerUnitGross($shopwareCart->getPrice()->getTotalPrice())
            ->setTitle('-'); // TODO?

        $basket->addBasketItem($basketItem);

        $basketResult = $client->createBasket($basket);
        $metaData = $this->metadataResourceHydrator->hydrateObject($salesChannelContext->getContext());
        $this->metadataResourceHydrator->setIsExpress($metaData, true);

        $config = $this->configReader->read($salesChannelContext->getSalesChannelId());
        $bookingMode = $config->get(ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL);
        $returnUrl = $this->generateUrl('frontend.unzer.paypal-express-return', [], UrlGeneratorInterface::ABSOLUTE_URL);
        if ($bookingMode === BookingMode::AUTHORIZE) {
            $authorization = (new Authorization(
                $shopwareCart->getPrice()->getTotalPrice(),
                $salesChannelContext->getCurrency()->getIsoCode(),
                $returnUrl
            ))
                ->setCheckoutType('express', $paymentTypeId);
            $resultTransaction = $client->performAuthorization($authorization, $paymentTypeId, null, $metaData, $basketResult);
        } else {
            $charge = (new Charge(
                $shopwareCart->getPrice()->getTotalPrice(),
                $salesChannelContext->getCurrency()->getIsoCode(),
                $returnUrl
            ))
                ->setCheckoutType('express', $paymentTypeId);
            $resultTransaction = $client->performCharge($charge, $paymentTypeId, null, $metaData, $basketResult);
        }

        $request->getSession()->set(ExpressCheckoutService::SESSION_PAYPAL_PAYMENT_ID, $resultTransaction->getPaymentId());
        $request->getSession()->set(ExpressCheckoutService::SESSION_PAYPAL_PAYMENT_TYPE_ID, $paymentTypeId);

        return new JsonResponse([
            'paymentId' => $resultTransaction->getPaymentId(),
            'redirectUrl' => $resultTransaction->getRedirectUrl(),
        ]);
    }

    #[Route(path: '/unzer/paypal-express-return', name: 'frontend.unzer.paypal-express-return', defaults: ['csrf_protected' => false], methods: ['POST', 'GET'])]
    public function paypalExpressReturn(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $paymentId = $request->getSession()->get('paypal-express-checkout-payment-id');
        if (empty($paymentId)) {
            $this->logger->error('paypalExpressReturn empty paymentId');

            return $this->redirectToRoute('frontend.checkout.cart.page'); // TODO
        }

        $client = $this->clientFactory->createClientFromSalesChannelId($salesChannelContext->getSalesChannelId(), $request);
        $payment = $client->fetchPayment($paymentId);

        try {
            $this->expressCheckoutService->startCheckoutSessionFromUnzerPayment($payment, PaymentInstaller::PAYMENT_ID_PAYPAL, $salesChannelContext);
        } catch (\Exception $e) {
            $this->logger->error('paypalExpressReturn Exception: ' . $e->getMessage());

            return $this->redirectToRoute('frontend.checkout.cart.page'); // TODO
        }
        $request->getSession()->set(ExpressCheckoutService::SESSION_SELECTED_EXPRESS_METHOD, PaymentInstaller::PAYMENT_ID_PAYPAL);

        return $this->redirectToRoute('frontend.checkout.confirm.page', ['isExpressCheckout' => 'true']);
    }

    #[Route(path: '/unzer/google-pay-express', name: 'frontend.unzer.google-express', defaults: ['csrf_protected' => false], methods: ['POST', 'GET'])]
    public function googleExpress(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $paymentTypeId = $request->get('paymentTypeId');
        $paymentData = $request->get('paymentData');
        try {
            $this->expressCheckoutService->startCheckoutSessionFromGooglePayData($paymentData, PaymentInstaller::PAYMENT_ID_GOOGLE_PAY, $salesChannelContext);
        } catch (\Exception $e) {
            $this->logger->error('googleExpress Exception: ' . $e->getMessage());

            return $this->redirectToRoute('frontend.checkout.cart.page'); // TODO
        }
        $request->getSession()->set(ExpressCheckoutService::SESSION_GOOGLE_PAYMENT_TYPE_ID, $paymentTypeId);
        $request->getSession()->set(ExpressCheckoutService::SESSION_SELECTED_EXPRESS_METHOD, PaymentInstaller::PAYMENT_ID_GOOGLE_PAY);

        return new JsonResponse([
            'redirectUrl' => $this->generateUrl('frontend.checkout.confirm.page', ['isExpressCheckout' => 'true']),
        ]);
    }

    #[Route(path: '/unzer/applepay-express', name: 'frontend.unzer.applepay-express', defaults: ['csrf_protected' => false], methods: ['POST', 'GET'])]
    public function applepayExpress(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $paymentTypeId = $request->get('paymentTypeId');
        $paymentData = $request->get('paymentData');
        $shippingContact = $request->get('shippingContact');
        $billingContact = $request->get('billingContact');
        try {
            $this->expressCheckoutService->startCheckoutSessionFromApplePayData($paymentData, $shippingContact, $billingContact, PaymentInstaller::PAYMENT_ID_APPLE_PAY_V2, $salesChannelContext);
        } catch (\Exception $e) {
            $this->logger->error('applepayExpress Exception: ' . $e->getMessage());

            return $this->redirectToRoute('frontend.checkout.cart.page'); // TODO
        }
        $request->getSession()->set(ExpressCheckoutService::SESSION_APPLEPAY_PAYMENT_TYPE_ID, $paymentTypeId);
        $request->getSession()->set(ExpressCheckoutService::SESSION_SELECTED_EXPRESS_METHOD, PaymentInstaller::PAYMENT_ID_APPLE_PAY_V2);

        return new JsonResponse([
            'redirectUrl' => $this->generateUrl('frontend.checkout.confirm.page', ['isExpressCheckout' => 'true']),
        ]);
    }
}
