<?php

declare(strict_types=1);

namespace HeidelPayment6\Controllers\Administration;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment6\Components\WebhookRegistrator\WebhookRegistrator;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * @RouteScope(scopes={"api"})
 */
class HeidelpayConfigurationController extends AbstractController
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
     * @Route("/api/v{version}/_action/heidel_payment/validate-credentials", name="api.action.heidelpay.validate.credentials", methods={"POST"})
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
     * @Route("/api/v{version}/_action/heidel_payment/register-webhooks", name="api.action.heidelpay.register.webhooks", methods={"POST"})
     */
    public function registerWebhooks(RequestDataBag $dataBag)
    {
        $responseCode = 200;
        $result       = [];

        try {
            if ($dataBag->has('clearWebhooks') && $dataBag->get('clearWebhooks', false)) {
                $result['clear'] = $this->webhookRegistrator->clearWebhooks($dataBag->get('selection', []));
            }

            $result['register'] = $this->webhookRegistrator->registerWebhook($dataBag->get('selection', []));
            $this->logger->info('Webhhooks registered!');
        } catch (HeidelpayApiException | Throwable $exception) {
            $this->logger->alert('Webhook registration failed!');
            $this->logger->error($exception->getMessage());
            $responseCode = 400;
        }

        return new JsonResponse($result, $responseCode);
    }
}
