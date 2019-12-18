<?php

declare(strict_types=1);

namespace HeidelPayment\Commands;

use HeidelPayment\Components\ClientFactory\ClientFactoryInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

    public function __construct(ClientFactoryInterface $clientFactory, Router $router)
    {
        $this->clientFactory = $clientFactory;
        $this->router        = $router;

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
        $style = new SymfonyStyle($input, $output);

        try {
            $client = $this->clientFactory->createClient();
            $client->deleteAllWebhooks();

            $host = parse_url($input->getArgument('host'));

            if (empty($host['host']) || empty($host['scheme'])) {
                $style->warning('The provided host is invalid.');

                return self::EXIT_CODE_INVALID_HOST;
            }

            $context = $this->router->getContext();
            $context->setHost($host['host']);
            $context->setScheme($host['scheme']);

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
}
