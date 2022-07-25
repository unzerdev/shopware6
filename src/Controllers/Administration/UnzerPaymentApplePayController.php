<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Administration;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;

/**
 * @RouteScope(scopes={"api"})
 */
class UnzerPaymentApplePayController extends AbstractController
{
    private ClientFactoryInterface $clientFactory;
    private LoggerInterface $logger;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->logger        = $logger;
    }

    /**
     * @Route("/api/_action/unzer-payment/apple-pay/certificates", name="api.action.unzer.apple-pay.update-certificates", methods={"POST"}, defaults={"_route_scope": {"api"}})
     * @Route("/api/v{version}/_action/unzer-payment/apple-pay/certificates", name="api.action.unzer.apple-pay.update-certificates.version", methods={"POST"}, defaults={"_route_scope": {"api"}})
     */
    public function updateApplePayCertificates(RequestDataBag $dataBag): JsonResponse
    {
        // TODO: Receive certificates as text parameters
        // TODO: Persist uploaded files in private filesystem, verify certificates (if openssl is present)
        // TODO: Send certificate data to Unzer
        return new JsonResponse(
            null,
            201
        );
    }

    /**
     * @Route("/api/_action/unzer-payment/apple-pay/certificates", name="api.action.unzer.apple-pay.check-certificates", methods={"GET"}, defaults={"_route_scope": {"api"}})
     * @Route("/api/v{version}/_action/unzer-payment/apple-pay/certificates", name="api.action.unzer.apple-pay.check-certificates.version", methods={"GET"}, defaults={"_route_scope": {"api"}})
     */
    public function checkApplePayCertificates(): JsonResponse
    {
        // TODO: Read uploaded certificates from private filesystem
        // TODO: Check if certificates are present and if they're not expired (if openssl is present)
        return new JsonResponse(
            null,
            200
        );
    }
}
