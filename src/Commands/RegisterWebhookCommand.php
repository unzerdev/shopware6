<?php

declare(strict_types=1);

namespace HeidelPayment6\Commands;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment6\Components\WebhookRegistrator\WebhookRegistrator;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;
use Throwable;

class RegisterWebhookCommand extends Command
{
    /** @var WebhookRegistrator */
    private $webhookRegistrator;

    /** @var EntityRepositoryInterface */
    private $domainRepository;

    public function __construct(WebhookRegistrator $webhookRegistrator, EntityRepositoryInterface $domainRepository)
    {
        $this->webhookRegistrator = $webhookRegistrator;
        $this->domainRepository   = $domainRepository;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('heidelpay:register-webhooks');
        $this->setDescription('Registers the heidelpay webhook');
        $this->addArgument('host', InputArgument::REQUIRED, 'Main Host of the shop. Example: http://www.domain.de');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style  = new SymfonyStyle($input, $output);
        $domain = $this->handleDomain($input->getArgument('host') ?? '', $style);

        if(null === $domain) {
            return WebhookRegistrator::EXIT_CODE_INVALID_HOST;
        }


        try {
            $context = $this->getContext($domain);

            if (!$context) {
                return WebhookRegistrator::EXIT_CODE_UNKNOWN_ERROR;
            }


            $this->webhookRegistrator->clearWebhooks();
            $result = $this->webhookRegistrator->registerWebhook();

            if(null === $result) {
                return WebhookRegistrator::EXIT_CODE_API_ERROR;
            }

            $message = sprintf('The webhooks have been registered to the following URL: %s', $result->getUrl());

            $style->success($message);
        } catch (HeidelpayApiException $exception) {
            $style->error($exception->getMerchantMessage());

            return WebhookRegistrator::EXIT_CODE_API_ERROR;
        } catch (Throwable $exception) {
            $style->error($exception->getMessage());

            return WebhookRegistrator::EXIT_CODE_UNKNOWN_ERROR;
        }

        return WebhookRegistrator::EXIT_CODE_SUCCESS;
    }

    protected function handleDomain(string $providedHost, SymfonyStyle $style): ?SalesChannelDomainEntity
    {
        $parsedHost   = parse_url($providedHost);

        if (!is_array($parsedHost) ||
            (is_array($parsedHost) && (empty($parsedHost['host']) || empty($parsedHost['scheme'])))) {
            $style->warning('The provided host is invalid.');

            return null;
        }

        $salesChannelDomain = $this->getSalesChannelByHost($providedHost);

        if (null === $salesChannelDomain) {
            $style->warning('The provided host does not exist in any saleschannel.');

            $possibleDomains = [];
            /** @var SalesChannelDomainEntity $salesChannelDomain */
            foreach ($this->domainRepository->search(new Criteria(), Context::createDefaultContext()) as $salesChannelDomain) {
                $possibleDomains[] = [$salesChannelDomain->getUrl()];
            }

            $style->table(['Possible domains'], $possibleDomains);
        }

        return $salesChannelDomain;
    }

    protected function getSalesChannelByHost(string $url): ?SalesChannelDomainEntity
    {
        $domainCriteria = new Criteria();
        $domainCriteria->addFilter(new EqualsFilter('url', $url));

        $salesChannelResult = $this->domainRepository->search($domainCriteria, Context::createDefaultContext());
        /** @var null|SalesChannelDomainEntity $firstResult */
        $firstResult = $salesChannelResult->first();

        return $firstResult
    }

    protected function getContext($host): ?RequestContext
    {
        $context = $this->router->getContext();

        if ($context !== null) {
            $context->setHost($host['host']);
            $context->setScheme($host['scheme']);
        }

        return $context;
    }
}
