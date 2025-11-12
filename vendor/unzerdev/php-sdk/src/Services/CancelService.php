<?php

namespace UnzerSDK\Services;

use RuntimeException;
use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Interfaces\CancelServiceInterface;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Unzer;
use function in_array;
use function is_string;

/**
 * This service provides for functionalities concerning cancel transactions.
 *
 * @link  https://docs.unzer.com/
 *
 */
class CancelService implements CancelServiceInterface
{
    /** @var Unzer */
    private $unzer;

    /**
     * PaymentService constructor.
     *
     * @param Unzer $unzer
     */
    public function __construct(Unzer $unzer)
    {
        $this->unzer = $unzer;
    }

    /**
     * @return Unzer
     */
    public function getUnzer(): Unzer
    {
        return $this->unzer;
    }

    /**
     * @param Unzer $unzer
     *
     * @return CancelService
     */
    public function setUnzer(Unzer $unzer): CancelService
    {
        $this->unzer = $unzer;
        return $this;
    }

    /**
     * @return ResourceService
     */
    public function getResourceService(): ResourceService
    {
        return $this->getUnzer()->getResourceService();
    }

    /**
     * {@inheritDoc}
     */
    public function cancelAuthorization(Authorization $authorization, ?float $amount = null): Cancellation
    {
        $cancellation = new Cancellation($amount);
        $cancellation->setPayment($authorization->getPayment())->setParentResource($authorization);

        /** @var Cancellation $cancellation */
        $cancellation = $this->getResourceService()->createResource($cancellation);
        return $cancellation;
    }

    /**
     * {@inheritDoc}
     */
    public function cancelAuthorizationByPayment($payment, ?float $amount = null): Cancellation
    {
        $authorization = $this->getResourceService()->fetchAuthorization($payment);
        return $this->cancelAuthorization($authorization, $amount);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelChargeById(
        $payment,
        string $chargeId,
        ?float $amount = null,
        ?string $reasonCode = null,
        ?string $referenceText = null,
        ?float $amountNet = null,
        ?float $amountVat = null
    ): Cancellation {
        $charge = $this->getResourceService()->fetchChargeById($payment, $chargeId);
        return $this->cancelCharge($charge, $amount, $reasonCode, $referenceText, $amountNet, $amountVat);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelCharge(
        Charge $charge,
        ?float $amount = null,
        ?string $reasonCode = null,
        ?string $referenceText = null,
        ?float $amountNet = null,
        ?float $amountVat = null
    ): Cancellation {
        $cancellation = new Cancellation($amount);
        $cancellation
            ->setReasonCode($reasonCode)
            ->setPayment($charge->getPayment())
            ->setPaymentReference($referenceText)
            ->setAmountNet($amountNet)
            ->setAmountVat($amountVat);
        $charge->addCancellation($cancellation);
        $this->getResourceService()->createResource($cancellation);

        return $cancellation;
    }

    /**
     * {@inheritDoc}
     */
    public function cancelPayment(
        $payment,
        ?float $amount = null,
        ?string $reasonCode = CancelReasonCodes::REASON_CODE_CANCEL,
        ?string $referenceText = null,
        ?float $amountNet = null,
        ?float $amountVat = null
    ): array {
        $paymentObject = $payment;
        if (is_string($payment)) {
            $paymentObject = $this->getResourceService()->fetchPayment($payment);
        }
        $paymentType = $paymentObject->getPaymentType();
        if ($paymentType !== null && $paymentType->supportsDirectPaymentCancel()) {
            $message = 'The used payment type is not supported by this cancel method. Please use Unzer::cancelAuthorizedPayment() or Unzer::cancelChargedPayment() instead.';
            throw new RuntimeException($message);
        }

        if (!$paymentObject instanceof Payment) {
            throw new RuntimeException('Invalid payment object.');
        }

        $remainingToCancel = $amount;

        $cancelWholePayment = $remainingToCancel === null;
        $cancellations      = [];
        $cancellation       = null;

        if ($cancelWholePayment || $remainingToCancel > 0.0) {
            $cancellation = $this->cancelPaymentAuthorization($paymentObject, $remainingToCancel);

            if ($cancellation instanceof Cancellation) {
                $cancellations[] = $cancellation;
                $remainingToCancel = $this->updateCancelAmount($remainingToCancel, $cancellation->getAmount());
                $cancellation = null;
            }
        }

        if (!$cancelWholePayment && $remainingToCancel <= 0.0) {
            return $cancellations;
        }

        $chargeCancels = $this->cancelPaymentCharges(
            $paymentObject,
            $reasonCode,
            $referenceText,
            $amountNet,
            $amountVat,
            $remainingToCancel
        );

        return array_merge($cancellations, $chargeCancels);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelPaymentAuthorization($payment, ?float $amount = null): ?Cancellation
    {
        $cancellation   = null;
        $completeCancel = $amount === null;

        $authorize = $payment->getAuthorization();
        if ($authorize !== null) {
            $cancelAmount = null;
            if (!$completeCancel) {
                $remainingAuthorized = $payment->getAmount()->getRemaining();
                $cancelAmount        = $amount > $remainingAuthorized ? $remainingAuthorized : $amount;

                // do not attempt to cancel if there is nothing left to cancel
                if ($cancelAmount === 0.0) {
                    return null;
                }
            }

            try {
                $cancellation = $authorize->cancel($cancelAmount);
            } catch (UnzerApiException $e) {
                $this->isExceptionAllowed($e);
            }
        }

        return $cancellation;
    }

    /**
     * @param Payment $payment
     * @param ?string $reasonCode
     * @param ?string $referenceText
     * @param ?float  $amountNet
     * @param ?float  $amountVat
     * @param ?float  $remainingToCancel
     *
     * @return array
     *
     * @throws UnzerApiException
     * @throws RuntimeException
     */
    public function cancelPaymentCharges(
        Payment $payment,
        ?string  $reasonCode,
        ?string  $referenceText,
        ?float   $amountNet,
        ?float   $amountVat,
        ?float   $remainingToCancel = null
    ): array {
        $cancellations = [];
        $cancelWholePayment = $remainingToCancel === null;

        /** @var array $charge */
        $charges = $payment->getCharges();
        $receiptAmount = $this->calculateReceiptAmount($charges);
        foreach ($charges as $index => $charge) {
            $cancelAmount = null;
            if (!$cancelWholePayment && $remainingToCancel <= $charge->getTotalAmount()) {
                $cancelAmount = $remainingToCancel;
            }

            /** @var Charge $charge */
            // Calculate the maximum cancel amount for initial transaction.
            if ($index === 0 && $charge->isPending()) {
                $maxReversalAmount = $this->calculateMaxReversalAmount($charge, $receiptAmount);
                /* If canceled and charged amounts are equal or higher than the initial charge, skip it,
                because there won't be anything left to cancel. */
                if ($maxReversalAmount <= 0) {
                    continue;
                }
                if ($maxReversalAmount < $cancelAmount) {
                    $cancelAmount = $maxReversalAmount;
                }
            }

            try {
                $cancellation = $charge->cancel($cancelAmount, $reasonCode, $referenceText, $amountNet, $amountVat);
            } catch (UnzerApiException $e) {
                $this->isExceptionAllowed($e);
                continue;
            }

            if ($cancellation instanceof Cancellation) {
                $cancellations[] = $cancellation;
                $remainingToCancel = $this->updateCancelAmount($remainingToCancel, $cancellation->getAmount());
                $cancellation = null;
            }

            // stop if the amount has already been cancelled
            if (!$cancelWholePayment && $remainingToCancel <= 0) {
                break;
            }
        }
        return $cancellations;
    }

    /**
     * {@inheritDoc}
     */
    public function cancelAuthorizedPayment($payment, ?Cancellation $cancellation = null): Cancellation
    {
        if ($cancellation === null) {
            $cancellation = new Cancellation();
        }

        $paymentResource = $this->getResourceService()->getPaymentResource($payment);

        // Authorization is required to build the proper resource path for the cancellation.
        $authorization = (new Authorization())->setParentResource($paymentResource);
        $cancellation->setPayment($paymentResource)
            ->setParentResource($authorization);

        /** @var Cancellation $cancellation */
        $cancellation = $this->getResourceService()->createResource($cancellation);
        return $cancellation;
    }

    /**
     * {@inheritDoc}
     */
    public function cancelChargedPayment($payment, ?Cancellation $cancellation = null): Cancellation
    {
        if ($cancellation === null) {
            $cancellation = new Cancellation();
        }
        $paymentResource = $this->getResourceService()->getPaymentResource($payment);

        // Charge is required to build the proper resource path for the cancellation.
        $charge = (new Charge())->setParentResource($paymentResource);
        $cancellation->setPayment($paymentResource)
            ->setParentResource($charge);

        /** @var Cancellation $cancellation */
        $cancellation = $this->getResourceService()->createResource($cancellation);
        return $cancellation;
    }

    /**
     * Throws exception if the passed exception is not to be ignored while cancelling charges or authorization.
     *
     * @param UnzerApiException $exception
     *
     * @throws UnzerApiException
     */
    private function isExceptionAllowed(UnzerApiException $exception): void
    {
        $allowedErrors = [
            ApiResponseCodes::API_ERROR_ALREADY_CANCELLED,
            ApiResponseCodes::API_ERROR_ALREADY_CHARGED,
            ApiResponseCodes::API_ERROR_TRANSACTION_CANCEL_NOT_ALLOWED,
            ApiResponseCodes::API_ERROR_ALREADY_CHARGED_BACK
        ];

        if (!in_array($exception->getCode(), $allowedErrors, true)) {
            throw $exception;
        }
    }

    /**
     * Calculates and returns the remaining amount to cancel.
     * Returns null if the whole payment is to be canceled.
     *
     * @param float|null $remainingToCancel
     * @param float      $amount
     *
     * @return float|null
     */
    private function updateCancelAmount(?float $remainingToCancel, float $amount): ?float
    {
        $cancelWholePayment = $remainingToCancel === null;
        if (!$cancelWholePayment) {
            $remainingToCancel = round($remainingToCancel - $amount, 4);
        }
        return $remainingToCancel;
    }

    protected function calculateReceiptAmount(array $charges): float
    {
        $receiptAmount = 0;
        // Sum up Amounts of all successful charges from the list.
        foreach ($charges as $charge) {
            if ($charge->isSuccess()) {
                $receiptAmount += $charge->getAmount();
            }
        }
        return $receiptAmount;
    }

    /** Calculate max reversal amount for a charge and round it to 4th digit.
     *
     * @param Charge $charge
     * @param float  $receiptAmount
     *
     * @return float
     */
    private function calculateMaxReversalAmount(Charge $charge, float $receiptAmount): float
    {
        return round($charge->getAmount() - $receiptAmount - $charge->getCancelledAmount(), 4);
    }
}
