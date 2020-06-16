<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\WebhookRegistrator;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Webhook;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Throwable;

class WebhookRegistrator
{
    public const EXIT_CODE_SUCCESS       = 0;
    public const EXIT_CODE_API_ERROR     = 1;
    public const EXIT_CODE_UNKNOWN_ERROR = 2;
    public const EXIT_CODE_INVALID_HOST  = 3;

    /** @var Heidelpay */
    private $client;

    /** @var Router */
    private $router;

    public function __construct(ClientFactoryInterface $clientFactory, Router $router)
    {
        $this->client = $clientFactory->createClient();
        $this->router = $router;
    }

    public function registerWebhook(): ?Webhook
    {
        try{
        $url = $this->router->generate('heidelpay.webhook.execute', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $result = $this->client->createWebhook($url, 'all');

        } catch (HeidelpayApiException $exception) {
            $this->client->debugLog($exception->getMessage());

            $result = null;
        } catch (Throwable $exception) {
            $this->client->debugLog($exception->getMessage());

            $result = null;
        }

        return $result;
    }

    public function clearWebhooks(): int
    {
        try{
            $this->client->deleteAllWebhooks();
        } catch (HeidelpayApiException $exception) {
            $this->client->debugLog($exception->getMessage());

            return self::EXIT_CODE_API_ERROR;
        } catch (Throwable $exception) {
            $this->client->debugLog($exception->getMessage());

            return self::EXIT_CODE_UNKNOWN_ERROR;
        }

        return self::EXIT_CODE_SUCCESS;
    }
}
