<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Card;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use UnzerPayment6\Components\PaymentTransitionMapper\Traits\HasBookingMode;

class CreditCardTransitionMapper extends AbstractTransitionMapper
{
    use HasBookingMode;

    private const BOOKING_MODE_KEY = ConfigReader::CONFIG_KEY_BOOKING_MODE_CARD;
    private const DEFAULT_MODE     = BookingMode::CHARGE;

    public function __construct(ConfigReaderInterface $configReader, EntityRepositoryInterface $orderTransactionRepository)
    {
        $this->configReader               = $configReader;
        $this->orderTransactionRepository = $orderTransactionRepository;
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

    protected function getResourceName(): string
    {
        return Card::getResourceName();
    }

    /**
     * @throws TransitionMapperException
     */
    protected function mapForChargeMode(Payment $paymentObject): string
    {
        return parent::getTargetPaymentStatus($paymentObject);
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

            throw new TransitionMapperException($this->getResourceName());
        }

        if ($this->stateMachineTransitionExists(AbstractTransitionMapper::CONST_KEY_AUTHORIZE)) {
            if ($paymentObject->isPending() && !empty($paymentObject->getAuthorization())) {
                return constant(sprintf('%s::%s', StateMachineTransitionActions::class, AbstractTransitionMapper::CONST_KEY_AUTHORIZE));
            }
        }

        return $this->checkForRefund($paymentObject, $this->mapPaymentStatus($paymentObject));
    }
}
