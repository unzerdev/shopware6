<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Storefront;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class UnzerPaymentDeviceController extends StorefrontController
{
    public function __construct(
        private readonly UnzerPaymentDeviceRepositoryInterface $deviceRepository
    )
    {
    }

    //TODO: evaluate if GET is the correct method for this route
    #[Route(path: '/unzer/deleteDevice', name: 'frontend.unzer.device.delete', methods: ['GET'])]
    /**
     * @Route("/unzer/deleteDevice", name="frontend.unzer.device.delete", methods={"GET"})
     */
    public function deleteDevice(Request $request, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        if (!$salesChannelContext->getCustomer()) {
            return new RedirectResponse($this->generateUrl('frontend.account.payment.page'));
        }

        $context  = $salesChannelContext->getContext();
        $deviceId = $request->get('id');
        $device   = $this->deviceRepository->read($deviceId, $context);

        if ($device === null || $device->getCustomerId() !== $salesChannelContext->getCustomer()->getId()) {
            return new RedirectResponse($this->generateUrl('frontend.account.payment.page'));
        }

        $this->deviceRepository->remove($deviceId, $context);

        return new RedirectResponse(
            $this->generateUrl('frontend.account.payment.page', ['deviceRemoved' => true])
        );
    }
}
