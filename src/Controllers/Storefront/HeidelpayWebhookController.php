<?php

declare(strict_types=1);

namespace HeidelPayment\Controllers\Storefront;

use HeidelPayment\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment\Components\Struct\Webhook;
use HeidelPayment\Components\WebhookHandler\WebhookHandlerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class HeidelpayWebhookController extends StorefrontController
{
    /** @var WebhookHandlerInterface[] */
    private $handlers;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(iterable $handlers, ConfigReaderInterface $configReader)
    {
        $this->handlers     = $handlers;
        $this->configReader = $configReader;
    }

    /**
     * @Route("/heidelpay/webhook", name="heidelpay.webhook.execute", methods={"POST", "GET"})
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

            $handler->execute($webhook, $salesChannelContext);
        }

        return new Response();
    }
}
