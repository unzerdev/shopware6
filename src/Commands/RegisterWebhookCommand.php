<?php

declare(strict_types=1);

namespace UnzerPayment6\Commands;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use UnzerPayment6\Components\WebhookRegistrator\WebhookRegistrator;
use UnzerPayment6\Components\WebhookRegistrator\WebhookRegistratorInterface;
use UnzerSDK\Exceptions\UnzerApiException;

class RegisterWebhookCommand extends Command
{
    private WebhookRegistratorInterface $webhookRegistrator;

    private EntityRepository $domainRepository;

    public function __construct(WebhookRegistratorInterface $webhookRegistrator, EntityRepository $domainRepository)
    {
        $this->webhookRegistrator = $webhookRegistrator;
        $this->domainRepository   = $domainRepository;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('unzer:register-webhooks');
        $this->setDescription('Register the unzer webhook');
        $this->addArgument('host', InputArgument::REQUIRED, 'Main Host of the shop. Example: http://www.domain.de');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $host = $input->getArgument('host') ?? '';

        if (!is_string($host)) {
            return WebhookRegistrator::EXIT_CODE_INVALID_HOST;
        }

        $domain = $this->handleDomain($host, $style);

        if ($domain === null) {
            return WebhookRegistrator::EXIT_CODE_INVALID_HOST;
        }

        try {
            $domainDataBag = new RequestDataBag([
                new RequestDataBag([
                    'id'  => $domain->getId(),
                    'url' => $domain->getUrl(),
                ]),
            ]);

            $result = $this->webhookRegistrator->registerWebhook($domainDataBag);
        } catch (UnzerApiException $exception) {
            $style->error($exception->getMerchantMessage());

            return WebhookRegistrator::EXIT_CODE_API_ERROR;
        } catch (Throwable $exception) {
            $style->error($exception->getMessage());

            return WebhookRegistrator::EXIT_CODE_UNKNOWN_ERROR;
        }

        if (empty($result)) {
            return WebhookRegistrator::EXIT_CODE_API_ERROR;
        }

        $style->success(
            sprintf('The webhooks have been registered to the following URL: %s', $host)
        );

        return WebhookRegistrator::EXIT_CODE_SUCCESS;
    }

    protected function handleDomain(string $providedHost, SymfonyStyle $style): ?SalesChannelDomainEntity
    {
        $parsedHost = parse_url($providedHost);

        if (!is_array($parsedHost) || empty($parsedHost['host']) || empty($parsedHost['scheme'])) {
            $style->warning('The provided host is invalid.');

            return null;
        }

        $salesChannelDomain = $this->getSalesChannelByHost($providedHost);

        if ($salesChannelDomain === null) {
            $style->warning('The provided host does not exist in any saleschannel.');

            $possibleDomains = [];
            /** @var SalesChannelDomainEntity $domainResult */
            foreach ($this->domainRepository->search(new Criteria(), Context::createDefaultContext()) as $domainResult) {
                $possibleDomains[] = [$domainResult->getUrl()];
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

        return $salesChannelResult->first();
    }
}
