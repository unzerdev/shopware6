<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Administration;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\WebhookRegistrator\WebhookRegistrator;

/**
 * @RouteScope(scopes={"api"})
 */
class UnzerPaymentConfigurationController extends AbstractController
{
    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var WebhookRegistrator */
    private $webhookRegistrator;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        LoggerInterface $logger,
        WebhookRegistrator $webhookRegistrator
    ) {
        $this->clientFactory      = $clientFactory;
        $this->logger             = $logger;
        $this->webhookRegistrator = $webhookRegistrator;
    }

    /**
     * @Route("/api/v{version}/_action/unzer_payment/validate-credentials", name="api.action.unzer.validate.credentials", methods={"POST"})
     */
    public function validateCredentials(RequestDataBag $dataBag)
    {
        $privateKey   = $dataBag->get('privateKey');
        $publicKey    = $dataBag->get('publicKey');
        $responseCode = 200;

        if (empty($privateKey) || empty($publicKey)) {
            return false;
        }

        try {
            $client        = $this->clientFactory->createClientFromPrivateKey($privateKey);
            $remoteKeypair = $client->fetchKeypair();

            if ($remoteKeypair->getPublicKey() !== $publicKey) {
                $responseCode = 400;
            }
        } catch (HeidelpayApiException $apiException) {
            $responseCode = 400;
        } catch (RuntimeException $ex) {
            $responseCode = 400;
        }

        if ($responseCode === 200) {
            $this->logger->info('API credentials test passed!');
        } else {
            $this->logger->alert('API credentials test failed!');
        }

        return new JsonResponse([], $responseCode);
    }

    /**
     * @Route("/api/v{version}/_action/unzer_payment/register-webhooks", name="api.action.unzer.webhooks.register", methods={"POST"})
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
     * @Route("/api/v{version}/_action/unzer_payment/clear-webhooks", name="api.action.unzer.webhooks.clear", methods={"POST"})
     */
    public function clearWebhooks(RequestDataBag $dataBag): JsonResponse
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
            $this->webhookRegistrator->clearWebhooks($dataBag->get('selection', [])),
            200
        );
    }
}
