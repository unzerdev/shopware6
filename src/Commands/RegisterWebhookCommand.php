<?php

declare(strict_types=1);

namespace HeidelPayment\Commands;

use HeidelPayment\Components\Client\ClientFactory;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Router;
use Throwable;

class RegisterWebhookCommand extends Command
{
    /** @var ClientFactory */
    private $clientFactory;

    /** @var Router */
    private $router;

    public function __construct(ClientFactory $clientFactory, Router $router)
    {
        $this->clientFactory = $clientFactory;
        $this->router        = $router;

        parent::__construct();
    }

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

            $result  = $client->createWebhook($this->router->generate('heidelpay_webhook'), 'all');
            $message = sprintf('The webhooks have been registered to the following URL: %s', $result->getUrl());

            $style->success($message);
        } catch (HeidelpayApiException $exception) {
            $style->error($exception->getClientMessage());
        } catch (Throwable $exception) {
            $style->error($exception->getMessage());
        }
    }
}
