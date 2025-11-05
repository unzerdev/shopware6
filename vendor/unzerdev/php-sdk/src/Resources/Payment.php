<?php

namespace UnzerSDK\Resources;

use RuntimeException;
use stdClass;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Constants\IdStrings;
use UnzerSDK\Constants\TransactionTypes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\EmbeddedResources\Amount;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Paypage;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Chargeback;
use UnzerSDK\Resources\TransactionTypes\Payout;
use UnzerSDK\Resources\TransactionTypes\PreAuthorization;
use UnzerSDK\Resources\TransactionTypes\Shipment;
use UnzerSDK\Services\IdService;
use UnzerSDK\Traits\HasInvoiceId;
use UnzerSDK\Traits\HasOrderId;
use UnzerSDK\Traits\HasPaymentState;
use UnzerSDK\Traits\HasTraceId;
use function is_string;

/**
 * This represents the payment resource.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Payment extends AbstractUnzerResource
{
    use HasPaymentState;
    use HasOrderId;
    use HasInvoiceId;
    use HasTraceId;

    /** @var string $redirectUrl */
    private $redirectUrl;

    /** @var Authorization $authorization */
    private $authorization;

    /** @var Payout $payout */
    private $payout;

    /** @var array $shipments */
    private $shipments = [];

    /** @var Charge[] $charges */
    private $charges = [];

    /** @var Chargeback[] $chargebacks */
    private $chargebacks = [];


    /**
     * Associative array using the ID of the cancellations as the key.
     *
     * @var array $reversals
     */
    private $reversals = [];

    /**
     * Associative array using the ID of the cancellations as the key.
     *
     * @var array $refunds
     */
    private $refunds = [];


    /** @var Customer $customer */
    private $customer;

    /** @var BasePaymentType $paymentType */
    private $paymentType;

    /** @var Amount $amount */
    protected $amount;

    /** @var Metadata|null $metadata */
    private $metadata;

    /** @var Basket $basket */
    private $basket;

    /** @var Paypage $payPage */
    private $payPage;

    /**
     * @param null $parent
     */
    public function __construct($parent = null)
    {
        $this->amount = new Amount();

        $this->setParentResource($parent);
    }

    /**
     * Returns the redirectUrl set by the API.
     *
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /**
     * Sets the redirectUrl via response from API.
     *
     * @param string|null $redirectUrl
     *
     * @return Payment
     */
    protected function setRedirectUrl(?string $redirectUrl): Payment
    {
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    /**
     * @return Chargeback[]
     */
    public function getChargebacks(): array
    {
        return $this->chargebacks;
    }

    /**
     * @param array $chargebacks
     */
    public function setChargebacks(array $chargebacks): void
    {
        $this->chargebacks = $chargebacks;
    }

    /**
     * Retrieves the Authorization object of this payment.
     * Fetches the Authorization if it has not been fetched before and the lazy flag is not set.
     * Returns null if the Authorization does not exist.
     *
     * @param bool $lazy Enables lazy loading if set to true which results in the object not being updated via
     *                   API and possibly containing just the meta data known from the Payment object response.
     *
     * @return Authorization|AbstractUnzerResource|null The Authorization object if it exists.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function getAuthorization(bool $lazy = false)
    {
        $authorization = $this->authorization;
        if (!$lazy && $authorization !== null) {
            return $this->getResource($authorization);
        }
        return $authorization;
    }

    /**
     * Sets the Authorization object.
     *
     * @param Authorization $authorize The Authorization object to be stored in the payment.
     *
     * @return Payment This Payment object.
     */
    public function setAuthorization(Authorization $authorize): Payment
    {
        $authorize->setPayment($this);
        $this->authorization = $authorize;
        return $this;
    }

    /**
     * Retrieves the Payout object of this payment.
     * Fetches the Payout if it has not been fetched before and the lazy flag is not set.
     * Returns null if the Payout does not exist.
     *
     * @param bool $lazy Enables lazy loading if set to true which results in the object not being updated via
     *                   API and possibly containing just the meta data known from the Payment object response.
     *
     * @return Payout|AbstractUnzerResource|null The Payout object if it exists.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function getPayout(bool $lazy = false)
    {
        $payout = $this->payout;
        if (!$lazy && $payout !== null) {
            return $this->getResource($payout);
        }
        return $payout;
    }

    /**
     * Sets the Payout object.
     *
     * @param Payout $payout The Payout object to be stored in the payment.
     *
     * @return Payment This Payment object.
     */
    public function setPayout(Payout $payout): Payment
    {
        $payout->setPayment($this);
        $this->payout = $payout;
        return $this;
    }

    /**
     * Returns an array containing all known Charges of this Payment.
     *
     * @return array
     */
    public function getCharges(): array
    {
        return $this->charges;
    }

    /**
     * Adds a Charge object to this Payment and stores it in the charges array.
     *
     * @param Charge $charge
     *
     * @return $this
     */
    public function addCharge(Charge $charge): self
    {
        $charge->setPayment($this);
        $this->charges[] = $charge;
        return $this;
    }

    /**
     * Adds a Charge object to this Payment and stores it in the charges array.
     *
     * @param Charge $chargeback
     *
     * @return $this
     */
    private function addChargeback(Chargeback $chargeback): self
    {
        $chargeback->setPayment($this);
        $this->chargebacks[] = $chargeback;
        return $this;
    }

    /**
     * Retrieves a Charge object from the charges array of this Payment object by its Id.
     * Fetches the Charge if it has not been fetched before and the lazy flag is not set.
     * Returns null if the Charge does not exist.
     *
     * @param string $chargeId The id of the Charge to be retrieved.
     * @param bool   $lazy     Enables lazy loading if set to true which results in the object not being updated via
     *                         API and possibly containing just the meta data known from the Payment object response.
     *
     * @return Charge|null The retrieved Charge object or null if it does not exist.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function getCharge(string $chargeId, bool $lazy = false): ?Charge
    {
        /** @var Charge $charge */
        foreach ($this->charges as $charge) {
            if ($charge->getId() === $chargeId) {
                if (!$lazy) {
                    $this->getResource($charge);
                }
                return $charge;
            }
        }
        return null;
    }

    /**
     * Retrieves a Chargeback object from the chargebacks array of this Payment object by its ID.
     * Fetches the Charge if it has not been fetched before and the lazy flag is not set.
     * Returns null if the Charge does not exist.
     *
     * @param string $chargeId The ID of the Charge to be retrieved.
     * @param bool   $lazy     Enables lazy loading if set to true which results in the object not being updated via
     *                         API and possibly containing just the meta data known from the Payment object response.
     *
     * @return Charge|null The retrieved Charge object or null if it does not exist.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function getChargeback(string $chargebackId, ?string $chargeId, bool $lazy = false): ?Chargeback
    {
        /** @var Chargeback $chargeback */
        foreach ($this->chargebacks as $chargeback) {
            $parentResource = $chargeback->getParentResource();
            if ($chargeback->getId() === $chargebackId) {
                if ($parentResource instanceof Charge && $parentResource->getId() !== $chargeId) {
                    continue;
                }
                if (!$lazy) {
                    $this->getResource($chargeback);
                }
                return $chargeback;
            }
        }
        return null;
    }

    /**
     * Retrieves a Charge object by its index in the charges array.
     * Fetches the Charge if it has not been fetched before and the lazy flag is not set.
     * Returns null if the Charge does not exist.
     *
     * @param int  $index The index of the desired Charge object within the charges array.
     * @param bool $lazy  Enables lazy loading if set to true which results in the object not being updated via
     *                    API and possibly containing just the meta data known from the Payment object response.
     *
     * @return AbstractUnzerResource|Charge|null The retrieved Charge object or null if it could not be found.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function getChargeByIndex(int $index, bool $lazy = false)
    {
        $resource = null;
        if (isset($this->getCharges()[$index])) {
            $resource = $this->getCharges()[$index];
            if (!$lazy) {
                $resource = $this->getResource($resource);
            }
        }
        return $resource;
    }

    /**
     * Reference this payment object to the passed Customer resource.
     * The Customer resource can be passed as Customer object or the Id of a Customer resource.
     * If the Customer object has not been created yet via API this is done automatically.
     *
     * @param Customer|string|null $customer The Customer object or the id of the Customer to be referenced by the Payment.
     *
     * @return Payment This Payment object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function setCustomer($customer): Payment
    {
        if (empty($customer)) {
            return $this;
        }

        $unzer = $this->getUnzerObject();

        /** @var Customer $customerObject */
        $customerObject = $customer;

        if (is_string($customer)) {
            $customerObject = $unzer->fetchCustomer($customer);
        } elseif ($customerObject instanceof Customer) {
            if ($customerObject->getId() === null) {
                $unzer->createCustomer($customerObject);
            }
        }

        $customerObject->setParentResource($unzer);
        $this->customer = $customerObject;
        return $this;
    }

    /**
     * Returns the Customer object referenced by this Payment.
     *
     * @return Customer|null The Customer object referenced by this Payment or null if no Customer could be found.
     */
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    /**
     * Reference this payment object to the passed PayPage resource.
     * The PayPage resource can be passed as PayPage object or the Id of a PayPage resource.
     * If the PayPage object has not been created yet via API this is done automatically.
     *
     * @param PayPage|string|null $payPage The PayPage object or the id of the PayPage to be referenced by the Payment.
     *
     * @return Payment This Payment object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function setPayPage($payPage): Payment
    {
        if (empty($payPage)) {
            return $this;
        }

        $unzer = $this->getUnzerObject();

        /** @var PayPage $payPageObject */
        $payPageObject = $payPage;

        if (is_string($payPage)) {
            $payPageObject = (new Paypage(0, '', ''))
                ->setId($payPage)
                ->setPayment($this);
        }

        $payPageObject->setParentResource($unzer);
        $this->payPage = $payPageObject;
        return $this;
    }

    /**
     * Returns the PayPage object referenced by this Payment.
     *
     * @return PayPage|null The PayPage object referenced by this Payment or null if no PayPage could be found.
     */
    public function getPayPage(): ?PayPage
    {
        return $this->payPage;
    }

    /**
     * Returns the Payment Type object referenced by this Payment or throws a RuntimeException if none exists.
     *
     * @return BasePaymentType|null The PaymentType referenced by this Payment.
     */
    public function getPaymentType(): ?BasePaymentType
    {
        return $this->paymentType;
    }

    /**
     * Sets the Payments reference to the given PaymentType resource.
     * The PaymentType can be either a PaymentType object or the id of a PaymentType resource.
     *
     * @param BasePaymentType|string|null $paymentType The PaymentType object or the id of the PaymentType to be referenced.
     *
     * @return Payment This Payment object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function setPaymentType($paymentType): Payment
    {
        if (empty($paymentType)) {
            return $this;
        }

        $unzer = $this->getUnzerObject();

        /** @var BasePaymentType $paymentTypeObject */
        $paymentTypeObject = $paymentType;
        if (is_string($paymentType)) {
            $paymentTypeObject = $unzer->fetchPaymentType($paymentType);
        } elseif ($paymentTypeObject instanceof BasePaymentType && !$paymentTypeObject instanceof Paypage) {
            if ($paymentTypeObject->getId() === null) {
                $unzer->createPaymentType($paymentType);
            }
        }

        $this->paymentType = $paymentTypeObject;
        return $this;
    }

    /**
     * @return Metadata|null
     */
    public function getMetadata(): ?Metadata
    {
        return $this->metadata;
    }

    /**
     * @param Metadata|null $metadata
     *
     * @return Payment
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function setMetadata(?Metadata $metadata): Payment
    {
        if (!$metadata instanceof Metadata) {
            return $this;
        }
        $this->metadata = $metadata;

        $unzer = $this->getUnzerObject();
        if ($this->metadata->getId() === null) {
            $unzer->getResourceService()->createResource($this->metadata->setParentResource($unzer));
        }

        return $this;
    }

    /**
     * @return Basket|null
     */
    public function getBasket(): ?Basket
    {
        return $this->basket;
    }

    /**
     * Sets the basket object and creates it automatically if it does not exist yet (i. e. does not have an id).
     *
     * @param Basket|null $basket
     *
     * @return Payment
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function setBasket(?Basket $basket): Payment
    {
        $this->basket = $basket;

        if (!$basket instanceof Basket) {
            return $this;
        }

        $unzer = $this->getUnzerObject();
        if ($this->basket->getId() === null) {
            $unzer->getResourceService()->createResource($this->basket->setParentResource($unzer));
        }

        return $this;
    }

    /**
     * Retrieves a Cancellation object of this payment by its Id.
     * I. e. refunds (charge cancellations) and reversals (authorize cancellations).
     * Fetches the Authorization if it has not been fetched before and the lazy flag is not set.
     * Returns null if the Authorization does not exist.
     *
     * @param string $cancellationId The id of the Cancellation object to be retrieved.
     * @param bool   $lazy           Enables lazy loading if set to true which results in the object not being updated
     *                               via API and possibly containing just the meta data known from the Payment object
     *                               response.
     *
     * @return Cancellation|null The retrieved Cancellation object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     *
     * @deprecated since 3.2.0 Please use getCancellation() method of a Charge or Authorization object instead.
     */
    public function getCancellation(string $cancellationId, bool $lazy = false): ?Cancellation
    {
        /** @var Cancellation $cancellation */
        foreach ($this->getCancellations() as $cancellation) {
            if ($cancellation->getId() === $cancellationId) {
                if (!$lazy) {
                    $this->getResource($cancellation);
                }
                return $cancellation;
            }
        }

        return null;
    }

    /**
     * Return an array containing all Cancellations of this Payment object
     * I. e. refunds (charge cancellations) and reversals (authorize cancellations).
     *
     * @return array The array containing all Cancellation objects of this Payment object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function getCancellations(): array
    {
        if (!empty($this->refunds) || !(empty($this->reversals))) {
            return array_merge(
                array_values($this->reversals),
                array_values($this->refunds)
            );
        }
        $refunds = [];

        /** @var Charge $charge */
        foreach ($this->getCharges() as $charge) {
            $refunds[] = $charge->getCancellations();
        }

        $authorization = $this->getAuthorization(true);
        return array_merge($authorization ? $authorization->getCancellations() : [], ...$refunds);
    }

    /**
     * Add a Shipment object to the shipments array of this Payment object.
     *
     * @param Shipment $shipment The Shipment object to be added to this Payment.
     *
     * @return Payment This payment Object.
     */
    public function addShipment(Shipment $shipment): Payment
    {
        $shipment->setPayment($this);
        $this->shipments[] = $shipment;
        return $this;
    }

    /**
     * Returns all Shipment transactions of this payment.
     *
     * @return array
     */
    public function getShipments(): array
    {
        return $this->shipments;
    }

    /**
     * Retrieves a Shipment object of this Payment by its id.
     *
     * @param string $shipmentId The id of the Shipment to be retrieved.
     * @param bool   $lazy       Enables lazy loading if set to true which results in the object not being updated via
     *                           API and possibly containing just the meta data known from the Payment object response.
     *
     * @return Shipment|null The retrieved Shipment object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function getShipment(string $shipmentId, bool $lazy = false): ?Shipment
    {
        /** @var Shipment $shipment */
        foreach ($this->getShipments() as $shipment) {
            if ($shipment->getId() === $shipmentId) {
                if (!$lazy) {
                    $this->getResource($shipment);
                }
                return $shipment;
            }
        }

        return null;
    }

    /**
     * Sets the Amount object of this Payment.
     * The Amount stores the total, remaining, charged and cancelled amount of this Payment.
     *
     * @param Amount $amount
     *
     * @return Payment
     */
    public function setAmount(Amount $amount): Payment
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Returns the Amount object of this Payment.
     * The Amount stores the total, remaining, charged and cancelled amount of this Payment.
     *
     * @return Amount The Amount object belonging to this Payment.
     */
    public function getAmount(): Amount
    {
        return $this->amount;
    }

    /**
     * Returns the currency of the amounts of this Payment.
     *
     * @return string The Currency string of this Payment.
     */
    public function getCurrency(): string
    {
        return $this->amount->getCurrency();
    }

    /**
     * Returns the initial transaction (Authorize or Charge) of the payment.
     *
     * @param bool $lazy
     *
     * @return AbstractTransactionType|null
     *
     * @throws UnzerApiException
     * @throws RuntimeException
     */
    public function getInitialTransaction(bool $lazy = false): ?AbstractTransactionType
    {
        return $this->getAuthorization($lazy) ?? $this->getChargeByIndex(0, $lazy);
    }

    /**
     * Sets the currency string of the amounts of this Payment.
     *
     * @param string $currency
     *
     * @return self
     */
    protected function setCurrency(string $currency): self
    {
        $this->amount->handleResponse((object)['currency' => $currency]);
        return $this;
    }

    /**
     * @return array Associative array with cancellation id as the key and the Cancellation object as value.
     */
    public function getRefunds(): array
    {
        return $this->refunds;
    }

    /**
     * @param array $refunds
     *
     * @return Payment
     */
    public function setRefunds(array $refunds): Payment
    {
        $this->refunds = $refunds;
        return $this;
    }

    /**
     * @param Cancellation $refund
     *
     * @return Payment
     */
    public function addRefund(Cancellation $refund): Payment
    {
        $this->refunds[$refund->getId()] = $refund;
        return $this;
    }

    /**
     * @return array Associative array with cancellation id as the key and the Cancellation object as value.
     */
    public function getReversals(): array
    {
        return $this->reversals;
    }

    /**
     * @param array $reversals
     *
     * @return Payment
     */
    public function setReversals(array $reversals): Payment
    {
        $this->reversals = $reversals;
        return $this;
    }

    /**
     * Adds a Cancellation to the associative reversal array with the cancellation id as the key. If a cancellation with
     * that ID already exists it will be overwritten.
     *
     * @param Cancellation $reversal
     *
     * @return Payment
     */
    public function addReversal(Cancellation $reversal): Payment
    {
        $this->reversals[$reversal->getId()] = $reversal;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'payments';
    }

    /**
     * {@inheritDoc}
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        parent::handleResponse($response, $method);

        if (isset($response->state->id)) {
            $this->setState($response->state->id);
        }

        if (isset($response->resources)) {
            $this->updateResponseResources($response->resources);
        }

        if (isset($response->transactions)) {
            $this->updateResponseTransactions($response->transactions);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getExternalId(): ?string
    {
        return $this->getOrderId();
    }

    /**
     * Performs a Cancellation transaction on the Payment.
     * If no amount is given a full cancel will be performed i. e. all Charges and Authorizations will be cancelled.
     *
     * @param float|null  $amount           The amount to be canceled.
     *                                      This will be sent as amountGross in case of Installment Secured payment method.
     * @param string|null $reasonCode       Reason for the Cancellation ref \UnzerSDK\Constants\CancelReasonCodes.
     * @param string|null $paymentReference A reference string for the payment.
     * @param float|null  $amountNet        The net value of the amount to be cancelled (Installment Secured only).
     * @param float|null  $amountVat        The vat value of the amount to be cancelled (Installment Secured only).
     *
     * @return Cancellation[] An array holding all Cancellation objects created with this cancel call.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function cancelAmount(
        ?float  $amount = null,
        ?string $reasonCode = CancelReasonCodes::REASON_CODE_CANCEL,
        ?string $paymentReference = null,
        ?float  $amountNet = null,
        ?float  $amountVat = null
    ): array {
        return $this->getUnzerObject()->cancelPayment($this, $amount, $reasonCode, $paymentReference, $amountNet, $amountVat);
    }

    /**
     * Cancel the given amount of the payments authorization.
     *
     * @param float|null $amount The amount to be cancelled. If null the remaining uncharged amount of the authorization
     *                           will be cancelled completely. If it exceeds the remaining uncharged amount the
     *                           cancellation will only cancel the remaining uncharged amount.
     *
     * @return Cancellation|null The resulting cancellation object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function cancelAuthorizationAmount(?float $amount = null): ?Cancellation
    {
        return $this->getUnzerObject()->cancelPaymentAuthorization($this, $amount);
    }

    /**
     * Performs a Charge transaction on the payment.
     *
     * @param float|null $amount The amount to be charged.
     *
     * @return Charge|AbstractUnzerResource The resulting Charge object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function charge(?float $amount = null): Charge
    {
        return $this->getUnzerObject()->chargePayment($this, $amount);
    }

    /**
     * Performs a Shipment transaction on this Payment.
     *
     * @param string|null $invoiceId The id of the invoice in the shop.
     * @param string|null $orderId   The id of the order in the shop.
     *
     * @return AbstractUnzerResource|Shipment The resulting Shipment object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function ship(?string $invoiceId = null, ?string $orderId = null)
    {
        return $this->getUnzerObject()->ship($this, $invoiceId, $orderId);
    }

    /**
     * @param array $transactions
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    private function updateResponseTransactions(array $transactions = []): void
    {
        if (empty($transactions)) {
            return;
        }

        foreach ($transactions as $transaction) {
            switch ($transaction->type) {
                case TransactionTypes::AUTHORIZATION:
                    $this->updateAuthorizationTransaction($transaction);
                    break;
                case TransactionTypes::PREAUTHORIZATION:
                    $this->updatePreAuthorizationTransaction($transaction);
                    break;
                case TransactionTypes::CHARGE:
                    $this->updateChargeTransaction($transaction);
                    break;
                case TransactionTypes::REVERSAL:
                    $this->updateReversalTransaction($transaction);
                    break;
                case TransactionTypes::REFUND:
                    $this->updateRefundTransaction($transaction);
                    break;
                case TransactionTypes::SHIPMENT:
                    $this->updateShipmentTransaction($transaction);
                    break;
                case TransactionTypes::PAYOUT:
                    $this->updatePayoutTransaction($transaction);
                    break;
                case TransactionTypes::CHARGEBACK:
                    $this->updateChargebackTransaction($transaction);
                    break;
                default:
                    // skip
                    break;
            }
        }
    }

    /**
     * Handles the resources from a response and updates the payment object accordingly.
     *
     * @param stdClass $resources
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     */
    private function updateResponseResources(stdClass $resources): void
    {
        if (isset($resources->paymentId)) {
            $this->setId($resources->paymentId);
        }

        $customerId = $resources->customerId ?? null;
        if (!empty($customerId)) {
            if ($this->customer instanceof Customer && $this->customer->getId() === $customerId) {
                $this->getResource($this->customer);
            } else {
                $this->customer = $this->getUnzerObject()->fetchCustomer($customerId);
            }
        }

        $payPageId = $resources->payPageId ?? null;
        if (!empty($payPageId)) {
            $this->setPayPage($payPageId);
        }

        if (isset($resources->typeId) && !empty($resources->typeId) && !$this->paymentType instanceof BasePaymentType) {
            $this->paymentType = $this->getUnzerObject()->fetchPaymentType($resources->typeId);
        }

        $metadataId = $resources->metadataId ?? null;
        if (!empty($metadataId)) {
            if ($this->metadata instanceof Metadata && $this->metadata->getId() === $metadataId) {
                $this->getResource($this->metadata);
            } else {
                $this->metadata = $this->getUnzerObject()->fetchMetadata($resources->metadataId);
            }
        }

        if (isset($resources->basketId) && !empty($resources->basketId) && !$this->basket instanceof Basket) {
            $this->basket = $this->getUnzerObject()->fetchBasket($resources->basketId);
        }
    }

    /**
     * This updates the local Authorization object referenced by this Payment with the given Authorization transaction
     * from the Payment response.
     *
     * @param stdClass $transaction The transaction from the Payment response containing the Authorization data.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    private function updateAuthorizationTransaction(stdClass $transaction): void
    {
        $transactionId = IdService::getResourceIdFromUrl($transaction->url, IdStrings::AUTHORIZE);
        $authorization = $this->getAuthorization(true);
        if (!$authorization instanceof Authorization) {
            $authorization = (new Authorization())->setPayment($this)->setId($transactionId);
            $this->setAuthorization($authorization);
        }

        $authorization->handleResponse($transaction);
    }

    /**
     * This updates the local Authorization object referenced by this Payment with the given Authorization transaction
     * from the Payment response.
     *
     * @param stdClass $transaction The transaction from the Payment response containing the Authorization data.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    private function updatePreAuthorizationTransaction(stdClass $transaction): void
    {
        $transactionId = IdService::getResourceIdFromUrl($transaction->url, IdStrings::PREAUTHORIZE);
        $authorization = $this->getAuthorization(true);
        if (!$authorization instanceof Authorization) {
            $authorization = (new PreAuthorization())->setPayment($this)->setId($transactionId);
            $this->setAuthorization($authorization);
        }

        $authorization->handleResponse($transaction);
    }

    /**
     * This updates the local Charge object referenced by this Payment with the given Charge transaction from the
     * Payment response.
     *
     * @param stdClass $transaction The transaction from the Payment response containing the Charge data.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    private function updateChargeTransaction(stdClass $transaction): void
    {
        $transactionId = IdService::getResourceIdFromUrl($transaction->url, IdStrings::CHARGE);
        $charge        = $this->getCharge($transactionId, true);
        if (!$charge instanceof Charge) {
            $charge = (new Charge())->setPayment($this)->setId($transactionId);
            $this->addCharge($charge);
        }

        $charge->handleResponse($transaction);
    }

    /**
     * This updates a local Authorization Cancellation object (aka. reversal) referenced by this Payment with the
     * given Cancellation transaction from the Payment response.
     *
     * @param stdClass $transaction The transaction from the Payment response containing the Cancellation data.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    private function updateReversalTransaction(stdClass $transaction): void
    {
        $transactionId = IdService::getResourceIdFromUrl($transaction->url, IdStrings::CANCEL);

        $isPaymentCancellation = IdService::isPaymentCancellation($transaction->url);
        if ($isPaymentCancellation) {
            $cancellation = (new Cancellation())->setPayment($this)->setId($transactionId);
            $this->addReversal($cancellation);
        } else {
            $initialTransaction = $this->getInitialTransaction(true);
            if (!$initialTransaction instanceof Authorization && !$initialTransaction instanceof Charge) {
                throw new RuntimeException('The initial transaction object (Authorize or Charge) can not be found.');
            }

            $cancellation = $initialTransaction->getCancellation($transactionId, true);
            if (!$cancellation instanceof Cancellation) {
                $cancellation = (new Cancellation())->setPayment($this)->setId($transactionId);
                $initialTransaction->addCancellation($cancellation);
            }
        }

        $cancellation->handleResponse($transaction);
    }

    /**
     * This updates a local Charge Cancellation object (aka. refund) referenced by this Payment with the given
     * Cancellation transaction from the Payment response.
     *
     * @param stdClass $transaction The transaction from the Payment response containing the Cancellation data.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    private function updateRefundTransaction(stdClass $transaction): void
    {
        $refundId = IdService::getResourceIdFromUrl($transaction->url, IdStrings::CANCEL);
        $isPaymentCancellation = IdService::isPaymentCancellation($transaction->url);
        if ($isPaymentCancellation) {
            $cancellation = (new Cancellation())->setPayment($this)->setId($refundId);
            $this->addRefund($cancellation);
        }

        if (!$isPaymentCancellation) {
            $chargeId = IdService::getResourceIdFromUrl($transaction->url, IdStrings::CHARGE);
            $charge = $this->getCharge($chargeId, true);
            if (!$charge instanceof Charge) {
                throw new RuntimeException('The Charge object can not be found.');
            }
            $cancellation = $charge->getCancellation($refundId, true);

            if (!$cancellation instanceof Cancellation) {
                $cancellation = (new Cancellation())->setPayment($this)->setId($refundId);
                $charge->addCancellation($cancellation);
            }
        }

        $cancellation->handleResponse($transaction);
    }

    /**
     * This updates the local Shipment object referenced by this Payment with the given Shipment transaction from the
     * Payment response.
     *
     * @param stdClass $transaction The transaction from the Payment response containing the Shipment data.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    private function updateShipmentTransaction(stdClass $transaction): void
    {
        $shipmentId = IdService::getResourceIdFromUrl($transaction->url, IdStrings::SHIPMENT);
        $shipment   = $this->getShipment($shipmentId, true);
        if (!$shipment instanceof Shipment) {
            $shipment = (new Shipment())->setId($shipmentId);
            $this->addShipment($shipment);
        }

        $shipment->handleResponse($transaction);
    }

    /**
     * This updates the local Payout object referenced by this Payment with the given Payout transaction from the
     * Payment response.
     *
     * @param stdClass $transaction The transaction from the Payment response containing the Payout data.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    private function updatePayoutTransaction(stdClass $transaction): void
    {
        $payoutId = IdService::getResourceIdFromUrl($transaction->url, IdStrings::PAYOUT);
        $payout   = $this->getPayout(true);
        if (!$payout instanceof Payout) {
            $payout = (new Payout())->setId($payoutId);
            $this->setPayout($payout);
        }

        $payout->handleResponse($transaction);
    }

    /**
     * This updates the local chargeback object referenced by this Payment with the given chargeback transaction from the
     * Payment response.
     *
     * @param stdClass $transaction The transaction from the Payment response containing the chargeback data.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    private function updateChargebackTransaction(stdClass $transaction): void
    {
        // does chargeback refer to a specific charge transaction
        // Get/create charge instance, if yes.
        // does chargeback already exist?
        // Add chargeback to charge transaction
        $isPaymentChargeback = IdService::isPaymentChargeback($transaction->url);
        $chargebackId = IdService::getResourceIdFromUrl($transaction->url, IdStrings::CHARGEBACK);


        if (!$isPaymentChargeback) {
            $chargeId = IdService::getResourceIdFromUrl($transaction->url, IdStrings::CHARGE);
            $chargeback = $this->getChargeback($chargebackId, $chargeId, true);
            $charge = $this->getCharge($chargeId, true);
            if (!$chargeback instanceof Chargeback) {
                $chargeback = (new Chargeback())->setId($chargebackId);
                $this->addChargeback($chargeback);

                if ($charge instanceof Charge) {
                    $charge->addChargeback($chargeback);
                    $chargeback->setParentResource($charge);
                }
            }
        } else {
            $chargeback = $this->getChargeback($chargebackId, null, true);
            if (!$chargeback instanceof Chargeback) {
                $chargeback = (new Chargeback())->setId($chargebackId);
                $this->addChargeback($chargeback);
            }
        }

        $chargeback->handleResponse($transaction);
    }
}
