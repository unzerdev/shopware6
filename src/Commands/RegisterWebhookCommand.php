<?php

declare(strict_types=1);

namespace HeidelPayment\Commands;

use HeidelPayment\Components\ClientFactory\ClientFactoryInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Throwable;

class RegisterWebhookCommand extends Command
{
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
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);

        try {
            $client = $this->clientFactory->createClient();
            $client->deleteAllWebhooks();

            $url = $this->router->generate('heidelpay_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $result  = $client->createWebhook($url, 'all');
            $message = sprintf('The webhooks have been registered to the following URL: %s', $result->getUrl());

            $style->success($message);
        } catch (HeidelpayApiException $exception) {
            $style->error($exception->getMerchantMessage());
        } catch (Throwable $exception) {
            $style->error($exception->getMessage());
        }
    }
}
