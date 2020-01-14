<?php

declare(strict_types=1);

namespace HeidelPayment6\EventListeners\StateMachine;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment6\Components\Event\AutomaticShippingNotificationEvent;
use HeidelPayment6\Components\Validator\AutomaticShippingValidatorInterface;
use HeidelPayment6\Installers\CustomFieldInstaller;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TransitionEventListener implements EventSubscriberInterface
{
    /** @var EntityRepositoryInterface */
    private $orderRepository;

    /** @var EntityRepositoryInterface */
    private $orderDeliveryRepository;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var AutomaticShippingValidatorInterface */
    private $automaticShippingValidator;
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $orderDeliveryRepository,
        EntityRepositoryInterface $transactionRepository,
        AutomaticShippingValidatorInterface $automaticShippingValidator,
        ClientFactoryInterface $clientFactory,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->orderRepository            = $orderRepository;
        $this->orderDeliveryRepository    = $orderDeliveryRepository;
        $this->clientFactory              = $clientFactory;
        $this->transactionRepository      = $transactionRepository;
        $this->logger                     = $logger;
        $this->automaticShippingValidator = $automaticShippingValidator;
        $this->eventDispatcher            = $eventDispatcher;
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

        if (!$order || !$this->automaticShippingValidator->shouldSendAutomaticShipping($order, $event->getToPlace())) {
            return;
        }

        $orderTransaction = $order->getTransactions()->first();
        $invoiceId        = $this->getInvoiceDocumentId($order->getDocuments());

        try {
            $client = $this->clientFactory->createClient($order->getSalesChannelId());
            $client->ship($orderTransaction->getId(), $invoiceId);
            $this->setCustomFields($event->getContext(), $orderTransaction);

            $this->eventDispatcher->dispatch(new AutomaticShippingNotificationEvent($order, $invoiceId, $event->getContext()));
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

            if ($orderDeliveryEntity === null) {
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

    private function getInvoiceDocumentId(DocumentCollection $documents): string
    {
        return $documents->filter(static function (DocumentEntity $entity) {
            if ($entity->getDocumentType()->getTechnicalName() === 'invoice') {
                return $entity;
            }

            return null;
        })->first()->getConfig()['documentNumber'];
    }
}
