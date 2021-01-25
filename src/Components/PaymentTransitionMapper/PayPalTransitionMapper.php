<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\PaymentTransitionMapper\Traits\HasBookingMode;

class PayPalTransitionMapper extends AbstractTransitionMapper
{
    use HasBookingMode;

    private const BOOKING_MODE_KEY = ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL;
    private const DEFAULT_MODE     = BookingMode::CHARGE;

    public function __construct(ConfigReaderInterface $configReader, EntityRepositoryInterface $orderTransactionRepository)
    {
        $this->configReader               = $configReader;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Paypal;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): string
    {
        $bookingMode = $this->getBookingMode($paymentObject);

        if ($bookingMode !== self::DEFAULT_MODE
            && $this->stateMachineTransitionExists(AbstractTransitionMapper::CONST_KEY_AUTHORIZE)
            && $paymentObject->isPending() && !empty($paymentObject->getAuthorization())) {
            return constant(sprintf('%s::%s', StateMachineTransitionActions::class, AbstractTransitionMapper::CONST_KEY_AUTHORIZE));
        }

        return parent::getTargetPaymentStatus($paymentObject);
    }

    protected function getResourceName(): string
    {
        return Paypal::getResourceName();
    }

    protected function isPendingAllowed(): bool
    {
        return true;
    }
}
