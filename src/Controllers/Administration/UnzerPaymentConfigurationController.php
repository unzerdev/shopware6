<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Administration;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\WebhookRegistrator\WebhookRegistratorInterface;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 * @RouteScope(scopes={"api"})
 * @Route(defaults={"_routeScope": {"api"}})
 */
class UnzerPaymentConfigurationController extends AbstractController
{
    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var WebhookRegistratorInterface */
    private $webhookRegistrator;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        LoggerInterface $logger,
        WebhookRegistratorInterface $webhookRegistrator
    ) {
        $this->clientFactory      = $clientFactory;
        $this->logger             = $logger;
        $this->webhookRegistrator = $webhookRegistrator;
    }

    /**
     * @Route("/api/_action/unzer-payment/validate-credentials", name="api.action.unzer.validate.credentials", methods={"POST"})
     * @Route("/api/v{version}/_action/unzer-payment/validate-credentials", name="api.action.unzer.validate.credentials.version", methods={"POST"})
     */
    public function validateCredentials(RequestDataBag $dataBag): JsonResponse
    {
        $privateKey   = $dataBag->get('privateKey');
        $publicKey    = $dataBag->get('publicKey');
        $responseCode = Response::HTTP_OK;

        if (empty($privateKey) || empty($publicKey)) {
            return new JsonResponse([], Response::HTTP_PRECONDITION_FAILED);
        }

        try {
            $client        = $this->clientFactory->createClientFromPrivateKey($privateKey);
            $remoteKeypair = $client->fetchKeypair();

            if ($remoteKeypair->getPublicKey() !== $publicKey) {
                $responseCode = Response::HTTP_BAD_REQUEST;
            }
        } catch (UnzerApiException $apiException) {
            $responseCode = Response::HTTP_BAD_REQUEST;
        } catch (RuntimeException $ex) {
            $responseCode = Response::HTTP_BAD_REQUEST;
        }

        if ($responseCode === 200) {
            $this->logger->info('API credentials test passed!');
        } else {
            $this->logger->alert('API credentials test failed!');
        }

        return new JsonResponse([], $responseCode);
    }

    /**
     * @Route("/api/_action/unzer-payment/register-webhooks", name="api.action.unzer.webhooks.register", methods={"POST"})
     * @Route("/api/v{version}/_action/unzer-payment/register-webhooks", name="api.action.unzer.webhooks.register.version", methods={"POST"})
     */
    public function registerWebhooks(RequestDataBag $dataBag): JsonResponse
    {
        /** @var DataBag $selection */
        $selection = $dataBag->get('selection', new DataBag());

        if ($selection->count() < 1) {
            return new JsonResponse([
                'missing' => [
                    'success' => false,
                    'message' => 'unzer-payment-settings.webhook.missing.selection',
                ],
            ], 200);
        }

        return new JsonResponse(
            $this->webhookRegistrator->registerWebhook($dataBag->get('selection', [])),
            200
        );
    }

    /**
     * @Route("/api/_action/unzer-payment/clear-webhooks", name="api.action.unzer.webhooks.clear", methods={"POST"})
     * @Route("/api/v{version}/_action/unzer-payment/clear-webhooks", name="api.action.unzer.webhooks.clear.version", methods={"POST"})
     */
    public function clearWebhooks(RequestDataBag $dataBag): JsonResponse
    {
        /** @var DataBag $selection */
        $selection = $dataBag->get('selection', new DataBag());

        if ($selection->count() < 1 || !$dataBag->has('privateKey')) {
            return new JsonResponse([
                'missing' => [
                    'success' => false,
                    'message' => 'unzer-payment-settings.webhook.missing.selection',
                ],
            ], 200);
        }

        return new JsonResponse(
            $this->webhookRegistrator->clearWebhooks($dataBag->get('privateKey'), $selection->all()),
            200
        );
    }

    /**
     * @Route("/api/_action/unzer-payment/get-webhooks", name="api.action.unzer.webhooks.get", methods={"POST"})
     * @Route("/api/v{version}/_action/unzer-payment/get-webhooks", name="api.action.unzer.webhooks.get.version", methods={"POST"})
     */
    public function getWebhooks(RequestDataBag $dataBag): JsonResponse
    {
        if (!$dataBag->has('privateKey') || empty($dataBag->get('privateKey'))) {
            return new JsonResponse();
        }

        return new JsonResponse(
            $this->webhookRegistrator->getWebhooks($dataBag->get('privateKey')),
            200
        );
    }
}
