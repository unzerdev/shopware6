<?php

namespace HeidelPayment\Components\Document;

use HeidelPayment\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment\Components\Struct\TransferInformation\TransferInformation;
use HeidelPayment\Installers\PaymentInstaller;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Shopware\Core\Checkout\Document\DocumentConfiguration;
use Shopware\Core\Checkout\Document\DocumentConfigurationFactory;
use Shopware\Core\Checkout\Document\DocumentGenerator\DocumentGeneratorInterface;
use Shopware\Core\Checkout\Document\DocumentGenerator\InvoiceGenerator as CoreInvoiceGenerator;
use Shopware\Core\Checkout\Document\Twig\DocumentTemplateRenderer;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class InvoiceGenerator implements DocumentGeneratorInterface
{
    public const SUPPORTED_PAYMENT_METHODS = [
        PaymentInstaller::PAYMENT_ID_INVOICE_GUARANTEED,
        PaymentInstaller::PAYMENT_ID_INVOICE_FACTORING,
        PaymentInstaller::PAYMENT_ID_INVOICE,
        PaymentInstaller::PAYMENT_ID_PRE_PAYMENT,
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

    /**
     * {@inheritdoc}
     */
    public function supports(): string
    {
        return $this->decoratedService->supports();
    }

    /**
     * {@inheritdoc}
     */
    public function generate(
        OrderEntity $order,
        DocumentConfiguration $config,
        Context $context,
        ?string $templatePath = null
    ): string {
        $heidelPaymentId = null;
        foreach ($order->getTransactions() as $transaction) {
            if (in_array($transaction->getPaymentMethodId(), self::SUPPORTED_PAYMENT_METHODS)) {
                $heidelPaymentId = $transaction->getId();

                break;
            }
        }

        if ($heidelPaymentId === null) {
            return $this->decoratedService->generate($order, $config, $context, $templatePath);
        }

        $payment      = $this->clientFactory->createClient($order->getSalesChannelId())->fetchPaymentByOrderId($heidelPaymentId);
        $transferInfo = $this->getTransferInformation($payment);

        $templatePath = $templatePath ?? CoreInvoiceGenerator::DEFAULT_TEMPLATE;

        return $this->documentTemplateRenderer->render(
            $templatePath,
            [
                'order'                        => $order,
                'config'                       => DocumentConfigurationFactory::mergeConfiguration($config, new DocumentConfiguration())->jsonSerialize(),
                'rootDir'                      => $this->rootDir,
                'context'                      => $context,
                'heidelpayTransferInformation' => $transferInfo,
            ],
            $context,
            $order->getSalesChannelId(),
            $order->getLanguageId(),
            $order->getLanguage()->getLocale()->getCode()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFileName(DocumentConfiguration $config): string
    {
        return $this->decoratedService->getFileName($config);
    }

    private function getTransferInformation(Payment $payment): ?TransferInformation
    {
        /** @var null|Charge $charge */
        $charge = $payment->getChargeByIndex(0);

        if ($charge === null) {
            return null;
        }

        return (new TransferInformation())->fromCharge($charge);
    }
}
