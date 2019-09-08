<?php

namespace HeidelPayment\Components\Document;

use HeidelPayment\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment\Installers\PaymentInstaller;
use Shopware\Core\Checkout\Document\DocumentConfiguration;
use Shopware\Core\Checkout\Document\DocumentConfigurationFactory;
use Shopware\Core\Checkout\Document\DocumentGenerator\DocumentGeneratorInterface;
use Shopware\Core\Checkout\Document\DocumentGenerator\InvoiceGenerator as CoreInvoiceGenerator;
use Shopware\Core\Checkout\Document\Twig\DocumentTemplateRenderer;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class InvoiceGenerator implements DocumentGeneratorInterface
{
    public const DECORATABLE_PAYMENT_METHOD_IDS = [
        PaymentInstaller::PAYMENT_ID_INVOICE_GUARANTEED,
        PaymentInstaller::PAYMENT_ID_INVOICE_FACTORING,
        PaymentInstaller::PAYMENT_ID_INVOICE,
    ];

    /** @var DocumentGeneratorInterface */
    private $decoratedService;

    /** @var DocumentTemplateRenderer */
    private $documentTemplateRenderer;

    /** @var string */
    private $rootDir;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    public function __construct(DocumentGeneratorInterface $decoratedService, ClientFactoryInterface $clientFactory, DocumentTemplateRenderer $documentTemplateRenderer, string $rootDir)
    {
        $this->decoratedService         = $decoratedService;
        $this->documentTemplateRenderer = $documentTemplateRenderer;
        $this->rootDir                  = $rootDir;
        $this->clientFactory            = $clientFactory;
    }

    public function supports(): string
    {
        return $this->decoratedService->supports();
    }

    public function generate(
        OrderEntity $order,
        DocumentConfiguration $config,
        Context $context,
        ?string $templatePath = null
    ): string {
        $heidelPaymentId = null;
        foreach ($order->getTransactions() as $transaction) {
            if (in_array($transaction->getPaymentMethodId(), self::DECORATABLE_PAYMENT_METHOD_IDS)) {
                $heidelPaymentId = $transaction->getId();
            }
        }

        if ($heidelPaymentId === null) {
            return $this->decoratedService->generate($order, $config, $context, $templatePath);
        }

        $this->clientFactory->createClient($order->getSalesChannelId())->fetchPaymentByOrderId();

        $templatePath = $templatePath ?? CoreInvoiceGenerator::DEFAULT_TEMPLATE;

        return $this->documentTemplateRenderer->render(
            $templatePath,
            [
                'order'   => $order,
                'config'  => DocumentConfigurationFactory::mergeConfiguration($config, new DocumentConfiguration())->jsonSerialize(),
                'rootDir' => $this->rootDir,
                'context' => $context,
            ],
            $context,
            $order->getSalesChannelId(),
            $order->getLanguageId(),
            $order->getLanguage()->getLocale()->getCode()
        );
    }

    public function getFileName(DocumentConfiguration $config): string
    {
        return $this->decoratedService->getFileName($config);
    }

    private function getBankInfo()
    {
    }
}
