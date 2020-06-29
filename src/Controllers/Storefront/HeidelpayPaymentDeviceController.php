<?php

declare(strict_types=1);

namespace HeidelPayment6\Controllers\Storefront;

use HeidelPayment6\DataAbstractionLayer\Repository\PaymentDevice\HeidelpayPaymentDeviceRepositoryInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HeidelpayPaymentDeviceController extends StorefrontController
{
    /** @var HeidelpayPaymentDeviceRepositoryInterface */
    private $deviceRepository;

    public function __construct(HeidelpayPaymentDeviceRepositoryInterface $deviceRepository)
    {
        $this->deviceRepository = $deviceRepository;
    }

    /**
     * @Route("/heidelpay/deleteDevice", name="heidelpay.device.delete", methods={"GET"})
     * @RouteScope(scopes={"storefront"})
     */
    public function deleteDevice(Request $request, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        if (!$salesChannelContext->getCustomer()) {
            $this->generateUrl('frontend.account.payment.page');
        }

        $context  = $salesChannelContext->getContext();
        $deviceId = $request->get('id');
        $device   = $this->deviceRepository->read($deviceId, $context);

        if ($device === null || $device->getCustomerId() !== $salesChannelContext->getCustomer()->getId()) {
            $this->generateUrl('frontend.account.payment.page');
        }

        $this->deviceRepository->remove($deviceId, $context);

        return new RedirectResponse(
            $this->generateUrl('frontend.account.payment.page', ['deviceRemoved' => true])
        );
    }
}
