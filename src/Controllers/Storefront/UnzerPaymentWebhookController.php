<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Storefront;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Traversable;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\Struct\Webhook;
use UnzerPayment6\Components\WebhookHandler\WebhookHandlerInterface;

/**
 * @RouteScope(scopes={"storefront"})
 */
class UnzerPaymentWebhookController extends StorefrontController
{
    /** @var Traversable|WebhookHandlerInterface[] */
    private $handlers;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Traversable $handlers, ConfigReaderInterface $configReader, LoggerInterface $logger)
    {
        $this->handlers     = $handlers;
        $this->configReader = $configReader;
        $this->logger       = $logger;
    }

    /**
     * @Route("/unzer/webhook", name="unzer.webhook.execute", methods={"POST", "GET"}, defaults={"csrf_protected": false})
     */
    public function execute(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        /** @var false|string $requestContent */
        $requestContent = $request->getContent();

        if (!$requestContent || empty($requestContent)) {
            $this->logger->error('The webhook was not executed due to missing data.');

            return new Response();
        }

        $webhook = new Webhook($requestContent);
        $config  = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        foreach ($this->handlers as $handler) {
            if ($webhook->getPublicKey() !== $config->get('publicKey')) {
                throw new UnauthorizedHttpException('Unzer Webhooks');
            }

            if (!$handler->supports($webhook, $salesChannelContext)) {
                continue;
            }

            try {
                $handler->execute($webhook, $salesChannelContext);
            } catch (Throwable $exception) {
                $this->logger->error(
                    'An exception was caught when handling a webhook, but this may not be a failure.',
                    [
                        'message' => $exception->getMessage(),
                        'code'    => $exception->getCode(),
                        'file'    => $exception->getFile(),
                        'line'    => $exception->getLine(),
                        'trace'   => $exception->getTraceAsString(),
                    ]
                );
            }
        }

        return new Response();
    }
}
