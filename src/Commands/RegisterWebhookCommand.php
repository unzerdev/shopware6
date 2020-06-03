<?php

declare(strict_types=1);

namespace HeidelPayment6\Commands;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
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
    private const EXIT_CODE_SUCCESS       = 0;
    private const EXIT_CODE_API_ERROR     = 1;
    private const EXIT_CODE_UNKNOWN_ERROR = 2;
    private const EXIT_CODE_INVALID_HOST  = 3;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var Router */
    private $router;

    /** @var EntityRepositoryInterface */
    private $domainRepository;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        Router $router,
        EntityRepositoryInterface $domainRepository
    ) {
        $this->clientFactory    = $clientFactory;
        $this->router           = $router;
        $this->domainRepository = $domainRepository;

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
        $style        = new SymfonyStyle($input, $output);
        $providedHost = $input->getArgument('host');
        $parsedHost   = parse_url($input->getArgument('host'));

        if (empty($parsedHost['host']) || empty($parsedHost['scheme'])) {
            $style->warning('The provided host is invalid.');

            return self::EXIT_CODE_INVALID_HOST;
        }

        if (!$this->domainExists($providedHost)) {
            $style->warning('The provided host does not exist in any saleschannel.');

            $possibleDomains = [];
            /** @var SalesChannelDomainEntity $salesChannelDomain */
            foreach ($this->domainRepository->search(new Criteria(), Context::createDefaultContext()) as $salesChannelDomain) {
                $possibleDomains[] = [$salesChannelDomain->getUrl()];
            }
            $style->table(['Possible domains'], $possibleDomains);

            return self::EXIT_CODE_INVALID_HOST;
        }

        try {
            $context = $this->getContext($parsedHost);

            if (!$context) {
                return self::EXIT_CODE_UNKNOWN_ERROR;
            }

            $client = $this->clientFactory->createClient();
            $client->deleteAllWebhooks();

            $url = $this->router->generate('heidelpay.webhook.execute', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $result  = $client->createWebhook($url, 'all');
            $message = sprintf('The webhooks have been registered to the following URL: %s', $result->getUrl());

            $style->success($message);
        } catch (HeidelpayApiException $exception) {
            $style->error($exception->getMerchantMessage());

            return self::EXIT_CODE_API_ERROR;
        } catch (Throwable $exception) {
            $style->error($exception->getMessage());

            return self::EXIT_CODE_UNKNOWN_ERROR;
        }

        return self::EXIT_CODE_SUCCESS;
    }

    protected function domainExists(string $url): bool
    {
        $domainCriteria = new Criteria();
        $domainCriteria->addFilter(new EqualsFilter('url', $url));

        $salesChannelResult = $this->domainRepository->search($domainCriteria, Context::createDefaultContext());
        /** @var null|SalesChannelDomainEntity $firstResult */
        $firstResult = $salesChannelResult->first();

        if (empty($firstResult)) {
            return false;
        }

        return true;
    }

    protected function getContext($host): ?RequestContext
    {
        $context = $this->router->getContext();

        if (!$context) {
            return null;
        }

        $context->setHost($host['host']);
        $context->setScheme($host['scheme']);

        return $context;
    }
}
