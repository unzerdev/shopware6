<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\WebhookRegistrator;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;
use Throwable;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerSDK\Exceptions\UnzerApiException;

class WebhookRegistrator implements WebhookRegistratorInterface
{
    public const EXIT_CODE_SUCCESS       = 0;
    public const EXIT_CODE_API_ERROR     = 1;
    public const EXIT_CODE_UNKNOWN_ERROR = 2;
    public const EXIT_CODE_INVALID_HOST  = 3;

    /** @var null|RequestContext */
    protected $context;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var Router */
    private $router;

    /** @var EntityRepository */
    private $salesChannelDomainRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        Router $router,
        EntityRepository $salesChannelDomainRepository,
        LoggerInterface $logger
    ) {
        $this->clientFactory                = $clientFactory;
        $this->router                       = $router;
        $this->salesChannelDomainRepository = $salesChannelDomainRepository;
        $this->logger                       = $logger;
    }

    public function registerWebhook(RequestDataBag $salesChannelDomains): array
    {
        $returnData = [];

        /** @var RequestDataBag $salesChannelDomain */
        foreach ($salesChannelDomains as $salesChannelDomain) {
            $salesChannelId    = $salesChannelDomain->get('salesChannelId');
            $preparationResult = $this->prepare($salesChannelDomain);
            $domainUrl         = $salesChannelDomain->get('url', '');
            $privateKey        = $salesChannelDomain->get('privateKey');

            if (!empty($preparationResult)) {
                $returnData[$preparationResult['key']] = $preparationResult['value'];

                continue;
            }

            try {
                $relativePath = $this->router->generate('frontend.unzer.webhook.execute', [], UrlGeneratorInterface::ABSOLUTE_PATH);
                $url          = $domainUrl . $relativePath;

                $result = $this->clientFactory
                    ->createClientFromPrivateKey($privateKey, $salesChannelId)
                    ->createWebhook($url, 'payment');

                $returnData[$domainUrl] = [
                    'success' => true,
                    'data'    => $result,
                    'message' => 'unzer-payment-settings.webhook.register.done',
                ];

                $this->logger->info(sprintf('Webhooks registered for domain %s', $domainUrl));
            } catch (UnzerApiException | Throwable $exception) {
                $returnData[$domainUrl] = [
                    'success' => false,
                    'message' => 'unzer-payment-settings.webhook.register.error',
                ];

                $this->logger->error(
                    sprintf('Webhook registration failed for domain %s', $domainUrl),
                    [
                        'message' => $exception->getMessage(),
                        'code'    => $exception->getCode(),
                        'file'    => $exception->getFile(),
                        'trace'   => $exception->getTraceAsString(),
                    ]
                );
            }
        }

        return $returnData;
    }

    public function clearWebhooks(string $privateKey, array $webhookIds): array
    {
        $returnData = [];

        foreach ($webhookIds as $webhookId => $data) {
            try {
                $this->clientFactory->createClientFromPrivateKey($privateKey)->deleteWebhook($webhookId);

                $returnData[$data['url']] = [
                    'success' => true,
                    'message' => 'unzer-payment-settings.webhook.clear.done',
                ];

                $this->logger->info(sprintf('Webhook %s (%s) deleted!', $webhookId, $data['url']));
            } catch (UnzerApiException | Throwable $exception) {
                $returnData[$data['url']] = [
                    'success' => false,
                    'message' => 'unzer-payment-settings.webhook.clear.error',
                ];

                $this->logger->error(
                    sprintf('Webhook deletion failed for %s (%s)!', $webhookId, $data['url']),
                    [
                        'message' => $exception->getMessage(),
                        'code'    => $exception->getCode(),
                        'file'    => $exception->getFile(),
                        'trace'   => $exception->getTraceAsString(),
                    ]
                );
            }
        }

        return $returnData;
    }

    public function getWebhooks(string $privateKey): array
    {
        $webhooks = $this->clientFactory->createClientFromPrivateKey($privateKey)->fetchAllWebhooks();
        $data     = [];

        foreach ($webhooks as $webhook) {
            $data[] = [
                'id'    => $webhook->getId(),
                'event' => $webhook->getEvent(),
                'url'   => $webhook->getUrl(),
            ];
        }

        return $data;
    }

    protected function prepare(DataBag $salesChannelDomain): array
    {
        if (!$salesChannelDomain->has('id') || !$salesChannelDomain->has('url')) {
            return [
                'key'   => 'missing',
                'value' => [
                    'success' => false,
                    'message' => 'unzer-payment-settings.webhook.missing.fields',
                ],
            ];
        }

        $salesChannelEntity = $this->getSalesChannelDomain(
            $salesChannelDomain->get('id', ''),
            $salesChannelDomain->get('url', '')
        );

        if ($salesChannelEntity === null) {
            return [
                'key'   => $salesChannelDomain->get('url', ''),
                'value' => [
                    'success' => false,
                    'message' => 'unzer-payment-settings.webhook.notFound.salesChannel',
                ],
            ];
        }

        $this->setContext($salesChannelEntity);

        if (!$this->context) {
            return [
                'key'   => $salesChannelDomain->get('url', ''),
                'value' => [
                    'success' => false,
                    'message' => 'unzer-payment-settings.webhook.missing.context',
                ],
            ];
        }

        return [];
    }

    protected function setContext(SalesChannelDomainEntity $host): void
    {
        $parsedUrl = parse_url($host->getUrl());
        $context   = $this->router->getContext();

        if ($context !== null && is_array($parsedUrl)) {
            if (array_key_exists('host', $parsedUrl) && !empty($parsedUrl['host'])) {
                $context = $context->setHost($parsedUrl['host']);
            }

            if (array_key_exists('scheme', $parsedUrl) && !empty($parsedUrl['scheme'])) {
                $context = $context->setScheme($parsedUrl['scheme']);
            }
        }

        $this->context = $context;
    }

    protected function getSalesChannelDomain(string $salesChannelId, string $url): ?SalesChannelDomainEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('salesChannelId');
        $criteria->addFilter(new EqualsFilter('id', $salesChannelId));
        $criteria->addFilter(new EqualsFilter('url', $url));

        return $this->salesChannelDomainRepository->search($criteria, Context::createDefaultContext())->first();
    }
}
