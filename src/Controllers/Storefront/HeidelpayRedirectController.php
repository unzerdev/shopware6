<?php

declare(strict_types=1);

namespace HeidelPayment\Controllers\Storefront;

use HeidelPayment\Components\ClientFactory\ClientFactoryInterface;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class HeidelpayRedirectController extends StorefrontController
{
    /** @var SessionInterface */
    private $session;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    public function __construct(
        SessionInterface $session,
        ClientFactoryInterface $clientFactory
    ) {
        $this->session       = $session;
        $this->clientFactory = $clientFactory;
    }

    /**
     * @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
     *
     * @Route("/heidelpay/finalizePayment", name="heidelpay_finalize_payment", methods={"GET"})
     */
    public function finalizePayment(): RedirectResponse
    {
        $metadataId      = $this->session->get('heidelpayMetadataId');
        $heidelpayClient = $this->clientFactory->createClient();

        $metadata          = $heidelpayClient->fetchMetadata($metadataId);
        $actualRedirectUrl = $metadata->getMetadata('returnUrl');

        return $this->redirect($actualRedirectUrl);
    }
}
