<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use UnzerPayment6\Components\PaymentTransitionMapper\Traits\HasBookingMode;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\TransactionTypes\Authorization;

class PayPalTransitionMapper extends AbstractTransitionMapper
{
    use HasBookingMode;

    private const BOOKING_MODE_KEY = ConfigReader::CONFIG_KEY_BOOKING_MODE_PAYPAL;
    private const DEFAULT_MODE     = BookingMode::CHARGE;

    public function __construct(ConfigReaderInterface $configReader, EntityRepository $orderTransactionRepository)
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

        if ($bookingMode !== self::DEFAULT_MODE) {
            return $this->mapForAuthorizeMode($paymentObject);
        }

        return parent::getTargetPaymentStatus($paymentObject);
    }

    protected function getResourceName(): string
    {
        return Paypal::getResourceName();
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

        if ($this->stateMachineTransitionExists(AbstractTransitionMapper::CONST_KEY_AUTHORIZE) && $paymentObject->isPending()) {
            $authorization = $paymentObject->getAuthorization();

            if ($authorization instanceof Authorization && $authorization->isSuccess()) {
                return constant(sprintf('%s::%s', StateMachineTransitionActions::class, AbstractTransitionMapper::CONST_KEY_AUTHORIZE));
            }
        }

        return $this->checkForRefund($paymentObject, $this->mapPaymentStatus($paymentObject));
    }
}
