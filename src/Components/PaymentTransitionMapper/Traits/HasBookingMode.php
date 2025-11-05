<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper\Traits;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\PaymentTransitionMapper\AbstractTransitionMapper;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;

/**
 * @property ConfigReaderInterface $configReader
 * @property EntityRepository $orderTransactionRepository
 */
trait HasBookingMode
{
    public function __construct(
        protected readonly ConfigReaderInterface $configReader,
        protected readonly EntityRepository $orderTransactionRepository
    ) {
    }

    protected function getBookingMode(string $orderTransactionId): string
    {
        $order = $this->getOrderByPayment($orderTransactionId);

        if ($order === null) {
            return self::DEFAULT_MODE;
        }

        $config = $this->configReader->read($order->getSalesChannelId());

        return $config->get(self::BOOKING_MODE_KEY, self::DEFAULT_MODE);
    }

    protected function getOrderByPayment(?string $orderTransactionId): ?OrderEntity
    {
        if (empty($orderTransactionId)) {
            return null;
        }

        $transaction = $this->getTransactionById($orderTransactionId);

        if ($transaction === null) {
            return null;
        }

        return $transaction->getOrder();
    }

    protected function getTransactionById(string $transactionId): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('order');

        $orderSearchResult = $this->orderTransactionRepository->search($criteria, Context::createDefaultContext());

        return $orderSearchResult->first();
    }

    protected function mapForAuthorizeMode(Payment $paymentObject): string
    {
        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_TRANSITION) {
                return $status;
            }

            $status = $this->checkForCancellation($paymentObject);

            if ($status !== self::INVALID_TRANSITION) {
                return $status;
            }

            return StateMachineTransitionActions::ACTION_FAIL;
        }

        if ($this->stateMachineTransitionExists(AbstractTransitionMapper::CONST_KEY_AUTHORIZE) && $paymentObject->isPending()) {
            $authorization = $paymentObject->getAuthorization();

            if ($authorization instanceof Authorization && $authorization->isSuccess()) {
                return \constant(\sprintf('%s::%s', StateMachineTransitionActions::class, AbstractTransitionMapper::CONST_KEY_AUTHORIZE));
            }
        }

        return $this->checkForRefund($paymentObject, $this->mapPaymentStatus($paymentObject));
    }
}
