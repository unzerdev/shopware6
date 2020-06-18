<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper;

use HeidelPayment6\Components\BookingMode;
use HeidelPayment6\Components\ConfigReader\ConfigReader;
use HeidelPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use HeidelPayment6\Components\PaymentTransitionMapper\Traits\HasBookingMode;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Card;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

class CreditCardTransitionMapper extends AbstractTransitionMapper
{
    use HasBookingMode;

    private const BOOKING_MODE_KEY = ConfigReader::CONFIG_KEY_BOOKINMODE_CARD;
    private const DEFAULT_MODE     = BookingMode::CHARGE;

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
}
