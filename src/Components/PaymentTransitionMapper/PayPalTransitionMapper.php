<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper;

use HeidelPayment6\Components\BookingMode;
use HeidelPayment6\Components\ConfigReader\ConfigReader;
use HeidelPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use HeidelPayment6\Components\PaymentTransitionMapper\Traits\HasBookingMode;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class PayPalTransitionMapper extends AbstractTransitionMapper
{
    use HasBookingMode;

    private const BOOKING_MODE_KEY = ConfigReader::CONFIG_KEY_BOOKINMODE_PAYPAL;
    private const DEFAULT_MODE     = BookingMode::CHARGE;

    /** @var ConfigReader */
    private $configReader;

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    public function __construct(ConfigReader $configReader, EntityRepositoryInterface $orderTransactionRepository)
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

        if ($bookingMode === self::DEFAULT_MODE) {
            return $this->mapForChargeMode($paymentObject);
        }

        return $this->mapForAuthorizeMode($paymentObject);
    }

    protected function mapForChargeMode(Payment $paymentObject): string
    {
        if ($paymentObject->isPending()) {
            throw new TransitionMapperException(Paypal::getResourceName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_TRANSITION) {
                return $status;
            }

            throw new TransitionMapperException(Paypal::getResourceName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }

    protected function mapForAuthorizeMode(Payment $paymentObject): string
    {
        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_TRANSITION) {
                return $status;
            }

            throw new TransitionMapperException(Paypal::getResourceName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
