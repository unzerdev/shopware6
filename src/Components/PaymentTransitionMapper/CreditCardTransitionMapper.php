<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper;

use HeidelPayment6\Components\ConfigReader\ConfigReader;
use HeidelPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Card;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

class CreditCardTransitionMapper extends AbstractTransitionMapper
{
    private const DEFAULT_MODE = 'charge';

    /** @var ConfigReader */
    private $configReader;

    /** @var EntityRepositoryInterface */
    private $orderRepository;

    public function __construct(ConfigReader $configReader, EntityRepositoryInterface $orderRepository)
    {
        $this->configReader    = $configReader;
        $this->orderRepository = $orderRepository;
    }

    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Card;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): string
    {
        $bookingMode = $this->getBookingMode($paymentObject);

        if ($bookingMode === self::DEFAULT_MODE) {
            return $this->mapForChargeMode($paymentObject);
        }

        return $this->mapForAuthorizeMode($paymentObject);
    }

    protected function mapForChargeMode(Payment $paymentObject): string
    {
        if ($paymentObject->isPending()) {
            throw new TransitionMapperException(Card::getResourceName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_TRANSITION) {
                return $status;
            }

            throw new TransitionMapperException(Card::getResourceName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }

    protected function mapForAuthorizeMode(Payment $paymentObject): string
    {
        /** @var Card $paymentType */
        $paymentType = $paymentObject->getPaymentType();

        if ($paymentType->get3ds() && $paymentObject->isPending()) {
            throw new TransitionMapperException(Card::getResourceName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_TRANSITION) {
                return $status;
            }

            throw new TransitionMapperException(Card::getResourceName());
        }

        if (count($paymentObject->getCharges()) > 0) {
            return StateMachineTransitionActions::ACTION_DO_PAY;
        }

        return $this->mapPaymentStatus($paymentObject);
    }

    protected function getBookingMode(Payment $paymentObject): string
    {
        $order = $this->getOrderByPayment($paymentObject->getOrderId());

        if (null === $order) {
            return self::DEFAULT_MODE;
        }

        $config = $this->configReader->read($order->getSalesChannelId());

        return $config->get(ConfigReader::CONFIG_KEY_BOOKINMODE_CARD, self::DEFAULT_MODE);
    }

    private function getOrderByPayment(?string $orderTransactionId): ?OrderEntity
    {
        if (empty($orderTransactionId)) {
            return null;
        }

        $transaction = $this->getTransactionById($orderTransactionId);

        if (null === $transaction) {
            return null;
        }

        return $transaction->getOrder();
    }

    private function getTransactionById(string $transactionId): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('order');

        $orderSearchResult = $this->orderRepository->search($criteria, Context::createDefaultContext());

        return $orderSearchResult->first();
    }
}
