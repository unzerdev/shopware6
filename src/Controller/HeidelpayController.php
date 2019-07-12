<?php

declare(strict_types=1);

namespace HeidelPayment\Controller;

use heidelpayPHP\Heidelpay;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class HeidelpayController extends StorefrontController
{
    /** @var SystemConfigService */
    protected $configService;

    /** @var SessionInterface */
    private $session;

    public function __construct(SystemConfigService $configService, SessionInterface $session)
    {
        $this->configService = $configService;
        $this->session       = $session;
    }

    /**
     * @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
     *
     * @Route("/heidelpay/finalizePayment", name="heidelpay_finalize_payment", methods={"GET"})
     */
    public function finalizePayment(): RedirectResponse
    {
        $metadataId      = $this->session->get('heidelpayMetadataId');
        $heidelpayClient = new Heidelpay($this->configService->get('HeidelPayment.config.privateKey'));

        $metadata          = $heidelpayClient->fetchMetadata($metadataId);
        $actualRedirectUrl = $metadata->getMetadata('returnUrl');

        return $this->redirect($actualRedirectUrl);
    }
}
