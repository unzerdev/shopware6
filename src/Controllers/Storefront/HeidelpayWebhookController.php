<?php

declare(strict_types=1);

namespace HeidelPayment6\Controllers\Storefront;

use HeidelPayment6\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment6\Components\Struct\Webhook;
use HeidelPayment6\Components\WebhookHandler\WebhookHandlerInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class HeidelpayWebhookController extends StorefrontController
{
    /** @var WebhookHandlerInterface[] */
    private $handlers;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(iterable $handlers, ConfigReaderInterface $configReader, LoggerInterface $logger)
    {
        $this->handlers     = $handlers;
        $this->configReader = $configReader;
        $this->logger       = $logger;
    }

    /**
     * @Route("/heidelpay/webhook", name="heidelpay.webhook.execute", methods={"POST", "GET"}, defaults={"csrf_protected": false})
     * @RouteScope(scopes={"storefront"})
     */
    public function execute(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $webhook = new Webhook($request->getContent());

        $config = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        foreach ($this->handlers as $handler) {
            if ($webhook->getPublicKey() !== $config->get('publicKey')) {
                throw new UnauthorizedHttpException('Heidelpay Webhooks');
            }

            if (!$handler->supports($webhook, $salesChannelContext)) {
                continue;
            }

            try {
                $handler->execute($webhook, $salesChannelContext);
            } catch(Throwable $exception) {
                $this->logger->info(
                    'Catched an exception while handling a webhook but this may not be a failure.',
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
