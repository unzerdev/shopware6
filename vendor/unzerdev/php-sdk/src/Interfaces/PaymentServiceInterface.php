<?php
/**
 * The interface for the PaymentService.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Interfaces;

use DateTime;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\EmbeddedResources\Paylater\InstallmentPlansQuery;
use UnzerSDK\Resources\InstalmentPlans;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\PaylaterInstallmentPlans;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Paypage;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Payout;
use UnzerSDK\Resources\TransactionTypes\Shipment;
use RuntimeException;

interface PaymentServiceInterface
{
    /**
     * Performs an Authorization transaction and returns the resulting Authorization resource.
     *
     * @param Authorization          $authorization The Authorization object containing transaction specific information.
     * @param BasePaymentType|string $paymentType   The PaymentType object or the id of the PaymentType to use.
     * @param Customer|string|null   $customer      The Customer object or the id of the customer resource to reference.
     * @param Metadata|null          $metadata      The Metadata object containing custom information for the payment.
     * @param Basket|null            $basket        The Basket object corresponding to the payment.
     *                                              The Basket object will be created automatically if it does not exist
     *                                              yet (i.e. has no id).
     *
     * @return Authorization The resulting object of the Authorization resource.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function performAuthorization(
        Authorization $authorization,
        $paymentType,
        $customer = null,
        Metadata      $metadata = null,
        Basket        $basket = null
    ): Authorization;

    /**
     * Update an Authorization transaction with PATCH method and returns the resulting Authorization resource.
     *
     * @param Payment|string $payment       The Payment object or ID the transaction belongs to.
     * @param Authorization  $authorization The Authorization object containing transaction specific information.
     *                                      The Basket object will be created automatically if it does not exist
     *                                      yet (i.e. has no id).
     *
     * @return Authorization The resulting object of the Authorization resource.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function updateAuthorization($payment, Authorization $authorization): Authorization;

    /**
     * Performs an Authorization transaction and returns the resulting Authorization resource.
     *
     * @param float                  $amount         The amount to authorize.
     * @param string                 $currency       The currency of the amount.
     * @param string|BasePaymentType $paymentType    The PaymentType object or the id of the PaymentType to use.
     * @param string                 $returnUrl      The URL used to return to the shop if the process requires leaving it.
     * @param Customer|string|null   $customer       The Customer object or the id of the customer resource to reference.
     * @param string|null            $orderId        A custom order id which can be set by the merchant.
     * @param Metadata|null          $metadata       The Metadata object containing custom information for the payment.
     * @param Basket|null            $basket         The Basket object corresponding to the payment.
     *                                               The Basket object will be created automatically if it does not exist
     *                                               yet (i.e. has no id).
     * @param bool|null              $card3ds        Enables 3ds channel for credit cards if available. This parameter is
     *                                               optional and will be ignored if not applicable.
     * @param string|null            $invoiceId      The external id of the invoice.
     * @param string|null            $referenceText  A reference text for the payment.
     * @param string|null            $recurrenceType Recurrence type used for recurring payment.
     *
     * @return Authorization The resulting object of the Authorization resource.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     *
     * @deprecated since 1.2.0.0 please use performAuthorization() instead.
     * @see performAuthorization
     *
     */
    public function authorize(
        $amount,
        $currency,
        $paymentType,
        $returnUrl,
        $customer = null,
        $orderId = null,
        $metadata = null,
        $basket = null,
        $card3ds = null,
        $invoiceId = null,
        $referenceText = null,
        $recurrenceType = null
    ): Authorization;

    /**
     * Performs a Charge transaction and returns the resulting Charge resource.
     *
     * @param Charge                 $charge      The Charge object containing transaction specific information.
     * @param string|BasePaymentType $paymentType The PaymentType object or the id of the PaymentType to use.
     * @param Customer|string|null   $customer    The Customer object or the id of the customer resource to reference.
     * @param Metadata|null          $metadata    The Metadata object containing custom information for the payment.
     * @param Basket|null            $basket      The Basket object corresponding to the payment.
     *                                            The Basket object will be created automatically if it does not exist
     *                                            yet (i.e. has no id).
     *
     * @return Charge The resulting object of the Charge resource.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function performCharge(
        Charge   $charge,
        $paymentType,
        $customer = null,
        Metadata $metadata = null,
        Basket   $basket = null
    ): Charge;

    /**
     * Update a Charge transaction with PATCH method and returns the resulting Charge resource.
     *
     * @param Payment|string $payment The Payment object or ID the transaction belongs to.
     * @param Charge         $charge  The Charge object containing transaction specific information.
     *                                The Basket object will be created automatically if it does not exist
     *                                yet (i.e. has no id).
     *
     * @return Charge The resulting object of the Charge resource.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function updateCharge($payment, Charge $charge): Charge;

    /**
     * Performs a Charge transaction and returns the resulting Charge resource.
     *
     * @param float                  $amount           The amount to charge.
     * @param string                 $currency         The currency of the amount.
     * @param string|BasePaymentType $paymentType      The PaymentType object or the id of the PaymentType to use.
     * @param string                 $returnUrl        The URL used to return to the shop if the process requires leaving it.
     * @param Customer|string|null   $customer         The Customer object or the id of the customer resource to reference.
     * @param string|null            $orderId          A custom order id which can be set by the merchant.
     * @param Metadata|null          $metadata         The Metadata object containing custom information for the payment.
     * @param Basket|null            $basket           The Basket object corresponding to the payment.
     *                                                 The Basket object will be created automatically if it does not exist
     *                                                 yet (i.e. has no id).
     * @param bool|null              $card3ds          Enables 3ds channel for credit cards if available. This parameter is
     *                                                 optional and will be ignored if not applicable.
     * @param string|null            $invoiceId        The external id of the invoice.
     * @param string|null            $paymentReference A reference text for the payment.
     * @param string|null            $recurrenceType   Recurrence type used for recurring payment.
     *                                                 See \UnzerSDK\Constants\RecurrenceTypes to find all supported types.
     *
     * @return Charge The resulting object of the Charge resource.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     *
     * @deprecated since 1.2.0.0 please use performCharge() instead.
     * @see performCharge
     *
     */
    public function charge(
        $amount,
        $currency,
        $paymentType,
        $returnUrl,
        $customer = null,
        $orderId = null,
        $metadata = null,
        $basket = null,
        $card3ds = null,
        $invoiceId = null,
        $paymentReference = null,
        $recurrenceType = null
    ): Charge;

    /**
     * Performs a Charge transaction for a previously authorized payment.
     * To perform a full charge of the authorized amount leave the amount null.
     *
     * @param string|Payment $payment The Payment object the Authorization to charge belongs to.
     * @param Charge         $charge  The Charge object containing transaction specific information.
     *
     * @return Charge The resulting object of the Charge resource.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function performChargeOnPayment(
        $payment,
        Charge $charge
    ): Charge;

    /**
     * Performs a Charge transaction for the Authorization of the given Payment object.
     * To perform a full charge of the authorized amount leave the amount null.
     *
     * @param string|Payment $payment   The Payment object the Authorization to charge belongs to.
     * @param float|null     $amount    The amount to charge.
     * @param string|null    $orderId   The order id from the shop.
     * @param string|null    $invoiceId The invoice id from the shop.
     *
     * @return Charge The resulting object of the Charge resource.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     *
     * @deprecated since 1.2.0.0 please use performChargeOnPayment() instead.
     *
     */
    public function chargeAuthorization(
        $payment,
        float $amount = null,
        string $orderId = null,
        string $invoiceId = null
    ): Charge;

    /**
     * Performs a Charge transaction for a specific Payment and returns the resulting Charge object.
     *
     * @param Payment|string $payment   The Payment object to be charged.
     * @param float|null     $amount    The amount to charge.
     * @param string|null    $orderId   The order id from the shop.
     * @param string|null    $invoiceId The invoice id from the shop.
     *
     * @return Charge The resulting Charge object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     *
     * @deprecated since 1.2.0.0 please use performChargeOnPayment() instead.
     *
     */
    public function chargePayment(
        $payment,
        float $amount = null,
        string $orderId = null,
        string $invoiceId = null
    ): Charge;

    /**
     * Performs a Payout transaction and returns the resulting Payout resource.
     *
     * @param float                  $amount        The amount to payout.
     * @param string                 $currency      The currency of the amount.
     * @param string|BasePaymentType $paymentType   The PaymentType object or the id of the PaymentType to use.
     * @param string                 $returnUrl     The URL used to return to the shop if the process requires leaving it.
     * @param Customer|string|null   $customer      The Customer object or the id of the customer resource to reference.
     * @param string|null            $orderId       A custom order id which can be set by the merchant.
     * @param Metadata|null          $metadata      The Metadata object containing custom information for the payment.
     * @param Basket|null            $basket        The Basket object corresponding to the payment.
     *                                              The Basket object will be created automatically if it does not exist
     *                                              yet (i.e. has no id).
     * @param string|null            $invoiceId     The external id of the invoice.
     * @param string|null            $referenceText A reference text for the payment.
     *
     * @return Payout|AbstractUnzerResource The resulting object of the Payout resource.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function payout(
        float    $amount,
        string   $currency,
        $paymentType,
        string   $returnUrl,
        $customer = null,
        string   $orderId = null,
        Metadata $metadata = null,
        Basket   $basket = null,
        string   $invoiceId = null,
        string   $referenceText = null
    ): Payout;

    /**
     * Performs a Shipment transaction and returns the resulting Shipment object.
     *
     * @param Payment|string $payment   The Payment object the the id of the Payment to ship.
     * @param string|null    $invoiceId The id of the invoice in the shop.
     * @param string|null    $orderId   The id of the order in shop.
     *
     * @return Shipment The resulting Shipment object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function ship($payment, string $invoiceId = null, string $orderId = null): Shipment;

    /**
     * Initializes a PayPage for charge transaction and returns the PayPage resource.
     * Use the id of the PayPage resource to render the embedded PayPage.
     * Or redirect the client to the redirectUrl of the PayPage to show him the PayPage hosted by Unzer.
     * Please keep in mind, that payment types requiring an authorization will not be shown on the PayPage when
     * initialized for charge.
     *
     * @param Paypage              $paypage  The PayPage resource to initialize.
     * @param Customer|string|null $customer The optional customer object.
     *                                       Keep in mind that payment types with mandatory customer object might not be
     *                                       available to the customer if no customer resource is referenced here.
     * @param Basket|null          $basket   The optional Basket object.
     *                                       Keep in mind that payment types with mandatory basket object might not be
     *                                       available to the customer if no basket resource is referenced here.
     * @param Metadata|null        $metadata The optional metadata resource.
     *
     * @return Paypage The updated PayPage resource.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function initPayPageCharge(
        Paypage  $paypage,
        Customer $customer = null,
        Basket   $basket = null,
        Metadata $metadata = null
    ): Paypage;

    /**
     * Initializes a PayPage for authorize transaction and returns the PayPage resource.
     * Use the id of the PayPage resource to render the embedded PayPage.
     * Or redirect the client to the redirectUrl of the PayPage to show him the PayPage hosted by Unzer.
     * Please keep in mind, that payment types requiring a charge transaction will not be shown on the PayPage when
     * initialized for authorize.
     *
     * @param Paypage              $paypage  The PayPage resource to initialize.
     * @param Customer|string|null $customer The optional customer object.
     *                                       Keep in mind that payment types with mandatory customer object might not be
     *                                       available to the customer if no customer resource is referenced here.
     * @param Basket|null          $basket   The optional Basket object.
     *                                       Keep in mind that payment types with mandatory basket object might not be
     *                                       available to the customer if no basket resource is referenced here.
     * @param Metadata|null        $metadata The optional metadata resource.
     *
     * @return Paypage The updated PayPage resource.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function initPayPageAuthorize(
        Paypage  $paypage,
        Customer $customer = null,
        Basket   $basket = null,
        Metadata $metadata = null
    ): Paypage;

    /**
     * Returns an InstallmentPlans object containing all available instalment plans.
     *
     * @param float         $amount            The amount to be charged via FlexiPay Rate.
     * @param string        $currency          The currency code of the transaction.
     * @param float         $effectiveInterest The effective interest rate.
     * @param DateTime|null $orderDate         The date the order took place, is set to today if left empty.
     *
     * @return InstalmentPlans|AbstractUnzerResource The object containing all possible instalment plans.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchInstallmentPlans(
        float    $amount,
        string   $currency,
        float    $effectiveInterest,
        DateTime $orderDate = null
    ): InstalmentPlans;

    /**
     * Returns an InstallmentPlans object containing all available instalment plan options.
     *
     * @param InstallmentPlansQuery $plansRequest
     *
     * @return PaylaterInstallmentPlans
     */
    public function fetchPaylaterInstallmentPlans(InstallmentPlansQuery $plansRequest): PaylaterInstallmentPlans;
}
