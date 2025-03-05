<?php
/**
 * The interface for the ResourceService.
 *
 * @link     https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Interfaces;

use RuntimeException;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Config;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\Keypair;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Paypage;
use UnzerSDK\Resources\Recurring;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Chargeback;
use UnzerSDK\Resources\TransactionTypes\Payout;
use UnzerSDK\Resources\TransactionTypes\Shipment;

interface ResourceServiceInterface
{
    /**
     * Retrieves an Payout resource via the API using the corresponding Payment or paymentId.
     * The Payout resource can not be fetched using its id since they are unique only within the Payment.
     * A Payment can have zero or one Payouts.
     *
     * @param Payment|string $payment The Payment object or the id of a Payment object whose Payout to fetch.
     *                                There can only be one payout object to a payment.
     *
     * @return Payout The Payout object of the given Payment.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchPayout($payment): Payout;

    /**
     * Activate recurring payment for the given payment type (if possible).
     *
     * @param string|BasePaymentType $paymentType    The payment to activate recurring payment for.
     * @param string                 $returnUrl      The URL to which the customer gets redirected in case of a 3ds
     *                                               transaction
     * @param string|null            $recurrenceType Recurrence type used for recurring payment.
     *
     * @return Recurring The recurring object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function activateRecurringPayment($paymentType, string $returnUrl, string $recurrenceType = null): Recurring;

    /**
     * Fetch and return payment by given payment id or payment object.
     * If a payment object is given it will be updated as well, thus you do not rely on the returned object.
     *
     * @param Payment|string $payment The payment object or paymentId to fetch.
     *
     * @return Payment The fetched payment object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchPayment($payment): Payment;

    /**
     * Fetch and return payment by given order id.
     *
     * @param string $orderId The external order id to fetch the payment by.
     *
     * @return Payment The fetched payment object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchPaymentByOrderId(string $orderId): Payment;

    /**
     * Fetch and return payPage by given payPageId or payPage object.
     * If a payPage object is given it will be updated as well, thus you do not rely on the returned object.
     *
     * @param Paypage|string $payPage The payment object or paymentId to fetch.
     *
     * @return Paypage The fetched payPage object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchPayPage($payPage): Paypage;

    /**
     * Fetch public key and configured payment types from API.
     *
     * @param bool $detailed If this flag is set detailed information are fetched.
     *
     * @return Keypair
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchKeypair(bool $detailed = false): Keypair;

    /**
     * Create Metadata resource.
     *
     * @param Metadata $metadata The Metadata object to be created.
     *
     * @return Metadata The fetched Metadata resource.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createMetadata(Metadata $metadata): Metadata;

    /**
     * Fetch and return Metadata resource.
     *
     * @param Metadata|string $metadata
     *
     * @return Metadata
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchMetadata($metadata): Metadata;

    /**
     * Creates and returns the given basket resource.
     *
     * @param Basket $basket The basket to be created.
     *
     * @return Basket The created Basket object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createBasket(Basket $basket): Basket;

    /**
     * Fetches and returns the given Basket (by object or id). Since the PAPI provides 2 basket versions, this method performs up to two request.
     * Firstly the basket gets fetched from the "v2" endpoint.
     * Only If PAPI returns a specific "basket not found" error the function tries to fetch from the "v1" basket endpoint.
     *
     * @see \UnzerSDK\Constants\ApiResponseCodes::API_ERROR_BASKET_NOT_FOUND
     *
     * @param Basket|string $basket Basket object or id of basket to be fetched.
     *
     * @return Basket The fetched Basket object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchBasket($basket): Basket;

    /**
     * Update the a basket resource with the given basket object (id must be set).
     *
     * @param Basket $basket
     *
     * @return Basket The updated Basket object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function updateBasket(Basket $basket): Basket;

    /**
     * Creates a PaymentType resource from the given PaymentType object.
     * This is used to create the payment object prior to any transaction.
     * Usually this will be done by the unzerUI components (https://docs.unzer.com/integrate/web-integration/#step-3-create-your-payment-method)
     *
     * @param BasePaymentType $paymentType
     *
     * @return BasePaymentType|AbstractUnzerResource
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createPaymentType(BasePaymentType $paymentType): BasePaymentType;

    /**
     * Updates the PaymentType resource with the given PaymentType object.
     *
     * @param BasePaymentType $paymentType The PaymentType object to be updated.
     *
     * @return BasePaymentType|AbstractUnzerResource The updated PaymentType object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is a error while using the SDK.
     */
    public function updatePaymentType(BasePaymentType $paymentType): BasePaymentType;

    /**
     * Fetch the payment type with the given Id from the API.
     *
     * @param string $typeId
     *
     * @return BasePaymentType|AbstractUnzerResource
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchPaymentType(string $typeId): BasePaymentType;

    /**
     * Create an API resource for the given customer object.
     *
     * @param Customer $customer The customer object to create the resource for.
     *
     * @return Customer The updated customer object after creation (it should have an id now).
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createCustomer(Customer $customer): Customer;

    /**
     * Create a resource for given customer or updates it if it already exists.
     *
     * @param Customer $customer The customer object to create/update the resource for.
     *
     * @return Customer The updated customer object after creation/update.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createOrUpdateCustomer(Customer $customer): Customer;

    /**
     * Fetch and return Customer object from API by the given id.
     *
     * @param Customer|string $customer The customer object or id to fetch the customer by.
     *
     * @return Customer The fetched customer object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchCustomer($customer): Customer;

    /**
     * Fetch and return customer object from API by the given external customer id.
     *
     * @param string $customerId The external customerId to fetch the customer resource by.
     *
     * @return Customer The fetched customer object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchCustomerByExtCustomerId(string $customerId): Customer;

    /**
     * Update and return a Customer object via API.
     *
     * @param Customer $customer The locally changed customer date to update the resource in API by.
     *
     * @return Customer The customer object after update.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function updateCustomer(Customer $customer): Customer;

    /**
     * Delete the given Customer resource.
     *
     * @param Customer|string $customer The customer to be deleted. Can be the customer object or its id.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function deleteCustomer($customer): void;

    /**
     * Fetch an authorization object by its payment object or id.
     * Authorization ids are not unique to a merchant but to the payment.
     * A Payment object can have zero or one authorizations.
     *
     * @param Payment|string $payment The payment object or payment id of which to fetch the authorization.
     *
     * @return Authorization|AbstractUnzerResource The fetched authorization.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchAuthorization($payment): Authorization;

    /**
     * Fetch a charge object by combination of payment id and charge id.
     * Charge ids are not unique to a merchant but to the payment.
     *
     * @param Payment|string $payment  The payment object or payment id to fetch the authorization from.
     * @param string         $chargeId The id of the charge to fetch.
     *
     * @return Charge|AbstractUnzerResource The fetched charge.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchChargeById($payment, string $chargeId): Charge;

    /**
     * Update local charge object.
     *
     * @param Charge $charge The charge object to be fetched.
     *
     * @return Charge|AbstractUnzerResource The fetched charge.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchCharge(Charge $charge): Charge;

    /**
     * Fetch a chargeback object by combination of payment id, chargeback id and charge id.
     * Chargeback ids are not unique to a merchant but to the payment.
     *
     * @param Payment|string $payment      The payment object or payment id to fetch the authorization from.
     * @param string         $chargebackId The id of the chargeback to fetch.
     *
     * @return Chargeback|AbstractUnzerResource The fetched chargeback.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchChargebackById(string $paymentId, string $chargebackId, ?string $chargeId): Chargeback;

    /**
     * Update local chargeback object.
     *
     * @param Chargeback $chargeback The chargeback object to be fetched.
     *
     * @return Chargeback|AbstractUnzerResource The fetched chargeback.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchChargeback(Chargeback $chargeback): Chargeback;

    /**
     * Fetch a cancellation on an authorization (aka reversal).
     *
     * @param Authorization $authorization  The authorization object for which to fetch the cancellation.
     * @param string        $cancellationId The id of the cancellation to fetch.
     *
     * @return Cancellation The fetched cancellation (reversal).
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchReversalByAuthorization(Authorization $authorization, string $cancellationId): Cancellation;

    /**
     * Fetches a cancellation resource on an authorization (aka reversal) via payment and cancellation id.
     *
     * @param Payment|string $payment        The payment object or id of the payment to fetch the cancellation for.
     * @param string         $cancellationId The id of the cancellation to fetch.
     *
     * @return Cancellation The fetched cancellation (reversal).
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchReversal($payment, string $cancellationId): Cancellation;

    /**
     * Fetch a cancellation resource on a charge (aka refund) via id.
     *
     * @param Payment|string $payment        The payment object or id of the payment to fetch the cancellation for.
     * @param string         $chargeId       The id of the charge to fetch the cancellation for.
     * @param string         $cancellationId The id of the cancellation to fetch.
     *
     * @return Cancellation The fetched cancellation (refund).
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchRefundById($payment, string $chargeId, string $cancellationId): Cancellation;

    /**
     * Fetch a cancellation resource on a Charge (aka refund).
     *
     * @param Charge $charge         The charge object to fetch the cancellation for.
     * @param string $cancellationId The id of the cancellation to fetch.
     *
     * @return Cancellation The fetched cancellation (refund).
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchRefund(Charge $charge, string $cancellationId): Cancellation;

    /**
     * Fetch a cancellation resource of a charged payment (aka refund).
     *
     * @param Payment|string $payment        The payment object to fetch the cancellation for.
     * @param string         $cancellationId The id of the cancellation to fetch.
     *
     * @return Cancellation The fetched cancellation (refund).
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchPaymentRefund($payment, string $cancellationId): Cancellation;

    /**
     * Fetch a cancellation resource of an authorized payment (aka reversal).
     *
     * @param Payment|string $payment        The payment object to fetch the cancellation for.
     * @param string         $cancellationId The id of the cancellation to fetch.
     *
     * @return Cancellation The fetched cancellation (refund).
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchPaymentReversal($payment, string $cancellationId): Cancellation;

    /**
     * Fetch a shipment resource of the given payment by id.
     *
     * @param Payment|string $payment    The payment object or id of the payment to fetch the cancellation for.
     * @param string         $shipmentId The id of the shipment to be fetched.
     *
     * @return Shipment The fetched shipment object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchShipment($payment, string $shipmentId): Shipment;

    /**
     * Get the configuration for the given payment type.
     *
     * @param BasePaymentType $paymentType
     * @param Config|null     $config      Can be used to add query params to the GET request.
     *
     * @return Config
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     *                           This will also occur if the given payment type has no configuration.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchConfig(BasePaymentType $paymentType, ?Config $config = null): Config;
}
