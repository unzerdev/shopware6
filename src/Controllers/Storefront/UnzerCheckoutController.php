<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Storefront;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\CheckoutController;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoader;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoader;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoader;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UnzerPayment6\Components\PaymentHandler\Exception\UnzerPaymentProcessException;

/**
 * @RouteScope(scopes={"storefront"})
 */
class UnzerCheckoutController extends CheckoutController
{
    /** @var CheckoutController */
    protected $innerService;

    /** @var LoggerInterface */
    protected $logger;

    /** @var CheckoutFinishPageLoader */
    private $finishPageLoader;

    public function __construct(
        CheckoutController $innerService,
        LoggerInterface $logger,
        CartService $cartService,
        CheckoutCartPageLoader $cartPageLoader,
        CheckoutConfirmPageLoader $confirmPageLoader,
        CheckoutFinishPageLoader $finishPageLoader,
        OrderService $orderService,
        PaymentService $paymentService,
        OffcanvasCartPageLoader $offcanvasCartPageLoader,
        EntityRepositoryInterface $orderRepository
    ) {
        $this->innerService     = $innerService;
        $this->logger           = $logger;
        $this->finishPageLoader = $finishPageLoader;

        parent::__construct(
            $cartService,
            $cartPageLoader,
            $confirmPageLoader,
            $finishPageLoader,
            $orderService,
            $paymentService,
            $offcanvasCartPageLoader,
            $orderRepository
        );
    }

    /**
     * {@inheritdoc}
     */
    public function order(RequestDataBag $data, SalesChannelContext $context, Request $request): Response
    {
        try {
            return $this->innerService->order($data, $context, $request);
        } catch (UnzerPaymentProcessException $apiException) {
            return $this->forwardToRoute(
                'frontend.checkout.finish.page',
                [
                    'orderId'                      => $apiException->getOrderId(),
                    'changedPayment'               => false,
                    'paymentFailed'                => true,
                    'unzerPaymentExceptionMessage' => $apiException->getClientMessage(),
                ]
            );
        }
    }

    public function finishPage(Request $request, SalesChannelContext $context): Response
    {
        if (!$context->getCustomer()) {
            return $this->redirectToRoute('frontend.checkout.register.page');
        }

        $unzerPaymentExceptionMessage = $request->get('unzerPaymentExceptionMessage', '');

        if ($request->get('paymentFailed', false) === true && !empty($unzerPaymentExceptionMessage)) {
            $page = $this->finishPageLoader->load($request, $context);

            $this->addFlash(
                'danger',
                sprintf(
                    '%s %s',
                    $unzerPaymentExceptionMessage,
                    $this->trans(
                        'UnzerPayment.finishPaymentFailed',
                        [
                            '%editOrderUrl%' => $this->generateUrl('frontend.account.edit-order.page', ['orderId' => $request->get('orderId')]),
                        ]
                    )
                )
            );

            return $this->renderStorefront(
                '@Storefront/storefront/page/checkout/finish/index.html.twig',
                ['page' => $page]
            );
        }

        return $this->innerService->finishPage($request, $context);
    }
}
