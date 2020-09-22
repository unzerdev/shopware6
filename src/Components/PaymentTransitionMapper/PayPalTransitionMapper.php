<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use UnzerPayment6\Components\PaymentTransitionMapper\Traits\HasBookingMode;

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

    protected function getResourceName(): string
    {
        return Paypal::getResourceName();
    }

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

            throw new TransitionMapperException($this->getResourceName());
        }

        return $this->checkForRefund($paymentObject, $this->mapPaymentStatus($paymentObject));
    }
}
