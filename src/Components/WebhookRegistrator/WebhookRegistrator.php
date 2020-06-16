<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\WebhookRegistrator;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use heidelpayPHP\Heidelpay;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

class WebhookRegistrator implements WebhookRegistratorInterface
{
    public const EXIT_CODE_SUCCESS       = 0;
    public const EXIT_CODE_API_ERROR     = 1;
    public const EXIT_CODE_UNKNOWN_ERROR = 2;
    public const EXIT_CODE_INVALID_HOST  = 3;

    /** @var Heidelpay */
    private $client;

    /** @var Router */
    private $router;

    /** @var EntityRepositoryInterface */
    private $salesChannelRepository;

    public function __construct(ClientFactoryInterface $clientFactory, Router $router, EntityRepositoryInterface $salesChannelRepository)
    {
        $this->client                 = $clientFactory->createClient();
        $this->router                 = $router;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function registerWebhook(RequestDataBag $salesChannelDomains): array
    {
        $returnData = [];

        /** @var RequestDataBag $salesChannelDomain */
        foreach ($salesChannelDomains as $salesChannelDomain) {
            if (!$salesChannelDomain->has('id') || !$salesChannelDomain->has('url')) {
                $returnData['missing'] = ['message' => 'heidel-payment-settings.webhook.missing.fields'];

                continue;
            }

            $salesChannelEntity = $this->getSalesChannelDomain($salesChannelDomain->get('id', ''), $salesChannelDomain->get('url', ''));

            if (null === $salesChannelEntity) {
                $returnData[$salesChannelDomain->get('url', '')] = ['message' => 'heidel-payment-settings.webhook.notFound.salesChannel'];

                continue;
            }

            $context = $this->getContext($salesChannelEntity);

            if (!$context) {
                $returnData[$salesChannelDomain->get('url', '')] = [
                    'message' => 'heidel-payment-settings.webhook.register.missing.context',
                ];

                continue;
            }

            $url = $this->router->generate('heidelpay.webhook.execute', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $returnData[$salesChannelDomain->get('url', '')] = [
                'data'    => $this->client->createWebhook($url, 'all'),
                'message' => 'heidel-payment-settings.webhook.register.done',
            ];
        }

        return $returnData;
    }

    /**
     * {@inheritdoc}
     */
    public function clearWebhooks(RequestDataBag $salesChannelDomains): array
    {
        $returnData = [];

        foreach ($salesChannelDomains as $salesChannelDomain) {
            if (!$salesChannelDomain->has('id') || !$salesChannelDomain->has('url')) {
                $returnData['missing'] = ['message' => 'heidel-payment-settings.webhook.missing.fields'];

                continue;
            }

            $salesChannelEntity = $this->getSalesChannelDomain($salesChannelDomain->get('id', ''), $salesChannelDomain->get('url', ''));

            if (null === $salesChannelEntity) {
                $returnData[$salesChannelDomain->get('url', '')] = ['message' => 'heidel-payment-settings.webhook.notFound.salesChannelDomain'];

                continue;
            }

            $context = $this->getContext($salesChannelEntity);

            $this->client->deleteAllWebhooks();
            $returnData[$salesChannelDomain->get('url', '')] = 'heidel-payment-settings.webhook.clear.done';
        }

        return $returnData;
    }

    protected function getContext(SalesChannelDomainEntity $host): ?RequestContext
    {
        $parsedUrl = parse_url($host->getUrl());
        $context   = $this->router->getContext();

        if ($context !== null) {
            $context->setHost($parsedUrl['host']);
            $context->setScheme($parsedUrl['scheme']);
        }

        return $context;
    }

    protected function getSalesChannelDomain(string $salesChannelId, string $url): ?SalesChannelDomainEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('salesChannelId');
        $criteria->addFilter(new EqualsFilter('id', $salesChannelId));
        $criteria->addFilter(new EqualsFilter('url', $url));

        $searchResult = $this->salesChannelRepository->search($criteria, Context::createDefaultContext());

        return $searchResult->first();
    }
}
