<?php

declare(strict_types=1);

namespace HeidelPayment6\EventListeners\StateMachine;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment6\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment6\Installers\CustomFieldInstaller;
use HeidelPayment6\Installers\PaymentInstaller;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Checkout\Document\DocumentCollection;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TransitionEventListener implements EventSubscriberInterface
{
    public const HANDLED_PAYMENT_METHODS = [
        PaymentInstaller::PAYMENT_ID_INVOICE_FACTORING,
        PaymentInstaller::PAYMENT_ID_INVOICE_GUARANTEED,
    ];

    /** @var EntityRepositoryInterface */
    private $orderRepository;

    /** @var EntityRepositoryInterface */
    private $orderDeliveryRepository;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $orderDeliveryRepository,
        EntityRepositoryInterface $transactionRepository,
        ConfigReaderInterface $configReader,
        ClientFactoryInterface $clientFactory,
        LoggerInterface $logger
    ) {
        $this->orderRepository         = $orderRepository;
        $this->orderDeliveryRepository = $orderDeliveryRepository;
        $this->configReader            = $configReader;
        $this->clientFactory           = $clientFactory;
        $this->transactionRepository   = $transactionRepository;
        $this->logger                  = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            StateMachineTransitionEvent::class => 'onStateMachineTransition',
        ];
    }

    public function onStateMachineTransition(StateMachineTransitionEvent $event): void
    {
        $order = $this->getOrderFromEvent($event);

        if (!$order) {
            return;
        }

        $config             = $this->configReader->read($order->getSalesChannelId());
        $configuredStatusId = $config->get('statusForAutomaticShippingNotification');

        if (empty($configuredStatusId) || $event->getToPlace()->getId() !== $configuredStatusId) {
            return;
        }

        $orderTransaction = $order->getTransactions()->first();

        if (!$orderTransaction || !in_array($orderTransaction->getPaymentMethodId(), self::HANDLED_PAYMENT_METHODS, false)) {
            return;
        }

        $invoiceId = $this->getInvoiceDocumentId($order->getDocuments());

        if (!$invoiceId) {
            return;
        }

        try {
            $client = $this->clientFactory->createClient($order->getSalesChannelId());
            $client->ship($orderTransaction->getId(), $invoiceId);

            $this->setCustomFields($event->getContext(), $orderTransaction);
        } catch (RuntimeException $exception) {
            $this->logger->error(sprintf('Error while executing automatic shipping notification for order [%s]: %s', $order->getOrderNumber(), $exception->getMessage()), [
                'trace' => $exception->getTrace(),
            ]);
        }
    }

    protected function setCustomFields(
        Context $context,
        OrderTransactionEntity $transaction
    ): void {
        $customFields = $transaction->getCustomFields() ?? [];
        $customFields = array_merge($customFields, [
            CustomFieldInstaller::HEIDELPAY_IS_SHIPPED => true,
        ]);

        $update = [
            'id'           => $transaction->getId(),
            'customFields' => $customFields,
        ];

        $this->transactionRepository->update([$update], $context);
    }

    private function getOrderFromEvent(StateMachineTransitionEvent $transitionEvent): ?OrderEntity
    {
        if ($transitionEvent->getEntityName() === OrderDeliveryDefinition::ENTITY_NAME) {
            $criteria = new Criteria([$transitionEvent->getEntityId()]);
            $criteria->addAssociations([
                'order',
                'order.transactions',
                'order.documents',
                'order.documents.documentType',
            ]);

            /** @var null|OrderDeliveryEntity $orderDeliveryEntity */
            $orderDeliveryEntity = $this->orderDeliveryRepository->search($criteria, $transitionEvent->getContext())->first();

            if (null === $orderDeliveryEntity) {
                return null;
            }

            return $orderDeliveryEntity->getOrder();
        }

        $criteria = new Criteria([$transitionEvent->getEntityId()]);
        $criteria->addAssociations([
            'transactions',
            'documents',
            'documents.documentType',
        ]);

        return $this->orderRepository->search($criteria, $transitionEvent->getContext())->first();
    }

    private function getInvoiceDocumentId(DocumentCollection $documents): ?string
    {
        $invoice = $documents->filter(static function (DocumentEntity $entity) {
            if ($entity->getDocumentType()->getTechnicalName() === 'invoice') {
                return $entity;
            }

            return null;
        })->first();

        return $invoice === null ? null : $invoice->getConfig()['documentNumber'];
    }
}
