<?php
/**
 * The interface for the CancelService.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Interfaces;

use RuntimeException;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;

interface CancelServiceInterface
{
    /**
     * Performs a Cancellation transaction and returns the resulting Cancellation object.
     * Performs a full cancel if the parameter amount is null.
     *
     * @param Authorization $authorization The Authorization to be canceled.
     * @param float|null    $amount        The amount to be canceled.
     *
     * @return Cancellation The resulting Cancellation object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function cancelAuthorization(Authorization $authorization, ?float $amount = null): Cancellation;

    /**
     * Performs a Cancellation transaction for the Authorization of the given Payment object.
     * Performs a full cancel if the parameter amount is null.
     *
     * @param Payment|string $payment The Payment object or the id of the Payment the Authorization belongs to.
     * @param float|null     $amount  The amount to be canceled.
     *
     * @return Cancellation Resulting Cancellation object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function cancelAuthorizationByPayment($payment, ?float $amount = null): Cancellation;

    /**
     * Performs a Cancellation transaction for the given Charge and returns the resulting Cancellation object.
     * Performs a full cancel if the parameter amount is null.
     *
     * @param Payment|string $payment       The Payment object or the id of the Payment the charge belongs to.
     * @param string         $chargeId      The id of the Charge to be canceled.
     * @param float|null     $amount        The amount to be canceled.
     *                                      This will be sent as amountGross in case of Installment Secured payment method.
     * @param string|null    $reasonCode    Reason for the Cancellation ref \UnzerSDK\Constants\CancelReasonCodes.
     * @param string|null    $referenceText A reference string for the payment.
     * @param float|null     $amountNet     The net value of the amount to be cancelled (Installment Secured only).
     * @param float|null     $amountVat     The vat value of the amount to be cancelled (Installment Secured only).
     *
     * @return Cancellation The resulting Cancellation object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function cancelChargeById(
        $payment,
        string $chargeId,
        ?float $amount = null,
        ?string $reasonCode = null,
        ?string $referenceText = null,
        ?float $amountNet = null,
        ?float $amountVat = null
    ): Cancellation;

    /**
     * Performs a Cancellation transaction and returns the resulting Cancellation object.
     * Performs a full cancel if the parameter amount is null.
     *
     * @param Charge      $charge        The Charge object to create the Cancellation for.
     * @param float|null  $amount        The amount to be canceled.
     *                                   This will be sent as amountGross in case of Installment Secured payment method.
     * @param string|null $reasonCode    Reason for the Cancellation ref \UnzerSDK\Constants\CancelReasonCodes.
     * @param string|null $referenceText A reference string for the payment.
     * @param float|null  $amountNet     The net value of the amount to be cancelled (Installment Secured only).
     * @param float|null  $amountVat     The vat value of the amount to be cancelled (Installment Secured only).
     *
     * @return Cancellation The resulting Cancellation object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function cancelCharge(
        Charge $charge,
        ?float $amount = null,
        ?string $reasonCode = null,
        ?string $referenceText = null,
        ?float $amountNet = null,
        ?float $amountVat = null
    ): Cancellation;

    /**
     * Performs a Cancellation transaction on the Payment.
     * If no amount is given a full cancel will be performed i.e. all Charges and Authorizations will be cancelled.
     *
     * @param Payment|string $payment       The Payment object or the id of the Payment to be cancelled.
     * @param float|null     $amount        The amount to be canceled.
     *                                      This will be sent as amountGross in case of Installment Secured payment method.
     * @param string|null    $reasonCode    Reason for the Cancellation ref \UnzerSDK\Constants\CancelReasonCodes.
     * @param string|null    $referenceText A reference string for the payment.
     * @param float|null     $amountNet     The net value of the amount to be cancelled (Installment Secured only).
     * @param float|null     $amountVat     The vat value of the amount to be cancelled (Installment Secured only).
     *
     * @return Cancellation[] An array holding all Cancellation objects created with this cancel call.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function cancelPayment(
        $payment,
        ?float $amount = null,
        ?string $reasonCode = CancelReasonCodes::REASON_CODE_CANCEL,
        ?string $referenceText = null,
        ?float $amountNet = null,
        ?float $amountVat = null
    ): array;

    /**
     * Performs a Cancellation transaction on the Payment. Should only be used for "paylater-invoice" payments.
     * If no Cancellation is given a full cancel will be performed.
     *
     * @param Payment|string    $payment      The Payment object or the id of the Payment to be cancelled.
     * @param Cancellation|null $cancellation
     *
     * @return Cancellation A Cancellation object created with this cancel call.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function cancelAuthorizedPayment($payment, ?Cancellation $cancellation = null): Cancellation;

    /**
     * Performs a Cancellation transaction on the Payment. Should only be used for "paylater-invoice" payments.
     * If no Cancellation is given a full cancel will be performed.
     *
     * @param Payment|string    $payment      The Payment object or the id of the Payment to be cancelled.
     * @param Cancellation|null $cancellation
     *
     * @return Cancellation A Cancellation object created with this cancel call.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function cancelChargedPayment($payment, ?Cancellation $cancellation = null): Cancellation;

    /**
     * Cancel the given amount of the payments authorization.
     *
     * @param Payment|string $payment The Payment object or the id of the Payment the authorization belongs to.
     * @param float|null     $amount  The amount to be cancelled. If null the remaining uncharged amount of the authorization
     *                                will be cancelled completely. If it exceeds the remaining uncharged amount the
     *                                cancellation will only cancel the remaining uncharged amount.
     *
     * @return Cancellation|null The resulting cancellation.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is a error while using the SDK.
     */
    public function cancelPaymentAuthorization($payment, ?float $amount = null): ?Cancellation;
}
