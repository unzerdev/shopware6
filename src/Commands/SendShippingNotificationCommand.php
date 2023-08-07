<?php

declare(strict_types=1);

namespace UnzerPayment6\Commands;

use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeEntity;
use Shopware\Core\Checkout\Document\DocumentCollection;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\Event\AutomaticShippingNotificationEvent;
use UnzerPayment6\Components\ShipService\ShipServiceInterface;
use UnzerPayment6\Components\Validator\AutomaticShippingValidatorInterface;
use UnzerPayment6\Installer\CustomFieldInstaller;
use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;

class SendShippingNotificationCommand extends Command
{
    private const EXIT_CODE_SUCCESS       = 0;
    private const EXIT_CODE_API_ERROR     = 1;
    private const EXIT_CODE_UNKNOWN_ERROR = 2;
    private const EXIT_CODE_NO_ORDERS     = 3;
    private const EXIT_CODE_CONFIGURATION = 4;

    private ConfigReaderInterface $configReader;

    private EntityRepository $transactionRepository;

    private Context $context;

    private EventDispatcherInterface $eventDispatcher;

    private ShipServiceInterface $shipService;

    public function __construct(
        ConfigReaderInterface $configReader,
        EntityRepository $transactionRepository,
        EventDispatcherInterface $eventDispatcher,
        ShipServiceInterface $shipService
    ) {
        $this->configReader          = $configReader;
        $this->transactionRepository = $transactionRepository;
        $this->context               = Context::createDefaultContext();
        $this->eventDispatcher       = $eventDispatcher;
        $this->shipService           = $shipService;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('unzer:send:shipping')
            ->setDescription('Send all shipping notifications for the necessary orders to Unzer GmbH.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config          = $this->configReader->read();
        $configuredState = $config->get(ConfigReader::CONFIG_KEY_SHIPPING_STATUS);

        if (empty($configuredState)) {
            $output->writeln('<error>Execution aborted: Please configure a value for [Order status for shipping notification] in your plugin settings.</error>');

            return self::EXIT_CODE_CONFIGURATION;
        }

        $transactions     = $this->getMatchingTransactions($configuredState);
        $transactionCount = $transactions->count();

        if ($transactionCount === 0) {
            $output->writeln('<info>No orders found for automatic shipping notification.</info>');

            return self::EXIT_CODE_NO_ORDERS;
        }

        $output->writeln(sprintf('<info>Found %s possible order(s) for automatic shipping notification</info>', $transactionCount));
        $currentTransactionCounter = 0;

        /** @var OrderTransactionEntity $transaction */
        foreach ($transactions as $transaction) {
            ++$currentTransactionCounter;

            $order = $transaction->getOrder();

            if ($order === null) {
                $output->writeln(sprintf('<error>Transaction %s has no order</error>', $transaction->getId()));

                continue;
            }

            if ($order->getDocuments() === null) {
                $output->writeln(sprintf('<error>Order %s has no documents</error>', $order->getOrderNumber()));

                continue;
            }

            $output->write(sprintf('(%s/%s) Order %s', $currentTransactionCounter, $transactionCount, $order->getOrderNumber()));

            $entityFilter = new DocumentTypeEntity();
            $entityFilter->setTechnicalName('invoice');
            $invoiceId = $this->getInvoiceDocumentId($order->getDocuments());

            try {
                $this->shipService->shipTransaction($transaction->getId(), $this->context);
                $this->setCustomFields($transaction);
                $this->eventDispatcher->dispatch(new AutomaticShippingNotificationEvent($order, $invoiceId, $this->context));

                $output->writeln("\t<info>OK</info>");
            } catch (UnzerApiException $apiException) {
                $output->writeln(sprintf("\t<error>%s</error>", $apiException->getMerchantMessage()));

                //Already insured but flag in DB missing!
                /** @var string $exceptionCode */
                $exceptionCode = $apiException->getCode();

                if ($exceptionCode === ApiResponseCodes::CORE_ERROR_INSURANCE_ALREADY_ACTIVATED) {
                    $this->setCustomFields($transaction);
                    $this->eventDispatcher->dispatch(new AutomaticShippingNotificationEvent($order, $invoiceId, $this->context));

                    return self::EXIT_CODE_SUCCESS;
                }

                return self::EXIT_CODE_API_ERROR;
            } catch (Throwable $exception) {
                $output->writeln(sprintf("\t<error>%s</error>", $exception->getMessage()));

                return self::EXIT_CODE_UNKNOWN_ERROR;
            }
        }

        return self::EXIT_CODE_SUCCESS;
    }

    protected function setCustomFields(
        OrderTransactionEntity $transaction
    ): void {
        $customFields = $transaction->getCustomFields() ?? [];
        $customFields = array_merge($customFields, [
            CustomFieldInstaller::UNZER_PAYMENT_IS_SHIPPED => true,
        ]);

        $update = [
            'id'           => $transaction->getId(),
            'customFields' => $customFields,
        ];

        $this->transactionRepository->update([$update], $this->context);
    }

    private function getMatchingTransactions(string $stateId): EntityCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter(sprintf('customFields.%s', CustomFieldInstaller::UNZER_PAYMENT_IS_SHIPPED), false),
            new EqualsAnyFilter('paymentMethodId', AutomaticShippingValidatorInterface::HANDLED_PAYMENT_METHODS),
            new EqualsFilter('order.deliveries.stateId', $stateId),
            new EqualsFilter('order.documents.documentType.technicalName', 'invoice')
        )->addAssociations([
            'order',
            'order.deliveries',
            'order.documents',
            'order.documents.documentType',
        ]);

        return $this->transactionRepository->search($criteria, $this->context)->getEntities();
    }

    private function getInvoiceDocumentId(DocumentCollection $documents): string
    {
        $document = $documents->filter(static function (DocumentEntity $entity) {
            if ($entity->getDocumentType() && $entity->getDocumentType()->getTechnicalName() === 'invoice') {
                return $entity;
            }

            return null;
        })->first();

        if ($document === null) {
            throw new \RuntimeException('No invoice document found');
        }

        return $document->getConfig()['documentNumber'];
    }
}
