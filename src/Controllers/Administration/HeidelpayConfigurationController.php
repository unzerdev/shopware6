<?php

namespace HeidelPayment6\Controllers\Administration;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use RuntimeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HeidelpayConfigurationController extends AbstractController
{
    /** @var ClientFactoryInterface */
    private $clientFactory;

    public function __construct(ClientFactoryInterface $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * @Route("/api/v{version}/_action/heidel_payment/validate-credentials", name="api.action.heidelpay.validate.credentials", methods={"POST"})
     * @RouteScope(scopes={"api"})
     */
    public function validateCredentials(RequestDataBag $dataBag, Context $context)
    {
        $privateKey     = $dataBag->get('privateKey');
        $publicKey      = $dataBag->get('publicKey');
        $salesChannelId = $dataBag->get('salesChannelId') ?? '';
        $responseCode   = 200;

        if (empty($privateKey) || empty($publicKey)) {
            return false;
        }

        try {
            $client        = $this->clientFactory->createClient($salesChannelId);
            $remoteKeypair = $client->fetchKeypair();

            if ($remoteKeypair->getPublicKey() !== $publicKey) {
                $responseCode = 400;
            }
        } catch (HeidelpayApiException $apiException) {
            $responseCode = 400;
        } catch (RuntimeException $ex) {
            $responseCode = 400;
        }

        return new JsonResponse([], $responseCode);
    }
}
