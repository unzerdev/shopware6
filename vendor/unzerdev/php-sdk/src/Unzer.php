<?php

namespace UnzerSDK;

use DateTime;
use RuntimeException;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Interfaces\CancelServiceInterface;
use UnzerSDK\Interfaces\DebugHandlerInterface;
use UnzerSDK\Interfaces\PaymentServiceInterface;
use UnzerSDK\Interfaces\ResourceServiceInterface;
use UnzerSDK\Interfaces\UnzerParentInterface;
use UnzerSDK\Interfaces\WebhookServiceInterface;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Authentication\Token;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Config;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\EmbeddedResources\Paylater\InstallmentPlansQuery;
use UnzerSDK\Resources\InstalmentPlans;
use UnzerSDK\Resources\Keypair;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\PaylaterInstallmentPlans;
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
use UnzerSDK\Resources\V2\Paypage as PaypageV2;
use UnzerSDK\Resources\Webhook;
use UnzerSDK\Services\CancelService;
use UnzerSDK\Services\HttpService;
use UnzerSDK\Services\JwtService;
use UnzerSDK\Services\PaymentService;
use UnzerSDK\Services\ResourceService;
use UnzerSDK\Services\WebhookService;
use UnzerSDK\Validators\PrivateKeyValidator;

/**
 * This is the Unzer object which is the base object providing all functionalities needed to
 * access the api.
 */
class Unzer implements
    UnzerParentInterface,
    PaymentServiceInterface,
    ResourceServiceInterface,
    WebhookServiceInterface,
    CancelServiceInterface
{
    public const BASE_URL = 'api.unzer.com';
    public const API_VERSION = 'v1';
    public const SDK_TYPE = 'UnzerPHP';
    public const SDK_VERSION = '3.11.0';

    /** @var string $key */
    private $key;

    /** @var string|null $locale */
    private $locale;

    /** @var string|null $clientIp */
    private $clientIp;

    /** @var ResourceServiceInterface $resourceService */
    private $resourceService;

    /** @var PaymentServiceInterface $paymentService */
    private $paymentService;

    /** @var WebhookServiceInterface $webhookService */
    private $webhookService;

    /** @var CancelServiceInterface $cancelService */
    private $cancelService;

    /** @var HttpService $httpService */
    private $httpService;

    /** @var DebugHandlerInterface $debugHandler */
    private $debugHandler;

    /** @var boolean $debugMode */
    private $debugMode = false;
    private $jwtToken;

    /**
     * Construct a new Unzer object.
     *
     * @param string $key The private key your received from your Unzer contact person.
     * @param ?string $locale The locale of the customer defining defining the translation (e.g. 'en-GB' or 'de-DE').
     *
     * @throws RuntimeException A RuntimeException will be thrown if the key is not of type private.
     *
     * @link https://docs.unzer.com/integrate/web-integration/#section-localization-and-languages
     *
     */
    public function __construct(string $key, ?string $locale = '')
    {
        $this->setKey($key);
        $this->setLocale($locale);

        $this->resourceService = new ResourceService($this);
        $this->paymentService = new PaymentService($this);
        $this->webhookService = new WebhookService($this);
        $this->cancelService = new CancelService($this);
        $this->httpService = new HttpService();
    }

    /**
     * Returns the set private key used to connect to the API.
     *
     * @return string The key that is currently set.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Sets your private key used to connect to the API.
     *
     * @param string $key The private key.
     *
     * @return Unzer This Unzer object.
     *
     * @throws RuntimeException Throws a RuntimeException when the key is invalid.
     *
     * @deprecated public access will be removed. Please create a new instance with a different keypair instead.
     */
    public function setKey(string $key): Unzer
    {
        if (!PrivateKeyValidator::validate($key)) {
            throw new RuntimeException('Illegal key: Use a valid private key with this SDK!');
        }

        $this->key = $key;
        return $this;
    }

    /**
     * Returns the set customer locale. This will be set as a request header field.
     *
     * @return string|null The locale of the customer.
     *                     Refer to the documentation under https://docs.unzer.com for a list of supported values.
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Sets the customer locale.
     *
     * @param string|null $locale The customer locale to set.
     *                            Ref. https://docs.unzer.com for a list of supported values.
     *
     * @return Unzer This Unzer object.
     */
    public function setLocale(?string $locale): Unzer
    {
        if ($locale === null) {
            return $this;
        }

        $this->locale = str_replace('_', '-', $locale);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getClientIp(): ?string
    {
        return $this->clientIp;
    }

    /**
     * Sets the clientIp. This will be set as a request header field.
     *
     * @param string|null $clientIp
     *
     * @return Unzer
     */
    public function setClientIp(?string $clientIp): Unzer
    {
        $this->clientIp = $clientIp;
        return $this;
    }

    /**
     * @param ResourceService $resourceService
     *
     * @return Unzer
     */
    public function setResourceService(ResourceService $resourceService): Unzer
    {
        $this->resourceService = $resourceService->setUnzer($this);
        return $this;
    }

    /**
     * Returns the ResourceService object.
     *
     * @return ResourceService The resource service object of this Unzer instance.
     */
    public function getResourceService(): ResourceService
    {
        return $this->resourceService;
    }

    /**
     * @param PaymentService $paymentService
     *
     * @return Unzer
     */
    public function setPaymentService(PaymentService $paymentService): Unzer
    {
        $this->paymentService = $paymentService->setUnzer($this);
        return $this;
    }

    /**
     * @return PaymentServiceInterface
     */
    public function getPaymentService(): PaymentServiceInterface
    {
        return $this->paymentService;
    }

    /**
     * @return WebhookServiceInterface
     */
    public function getWebhookService(): WebhookServiceInterface
    {
        return $this->webhookService;
    }

    /**
     * @param WebhookServiceInterface $webhookService
     *
     * @return Unzer
     */
    public function setWebhookService(WebhookServiceInterface $webhookService): Unzer
    {
        $this->webhookService = $webhookService;
        return $this;
    }

    /**
     * @return CancelServiceInterface
     */
    public function getCancelService(): CancelServiceInterface
    {
        return $this->cancelService;
    }

    /**
     * @param CancelService $cancelService
     *
     * @return Unzer
     */
    public function setCancelService(CancelService $cancelService): Unzer
    {
        $this->cancelService = $cancelService->setUnzer($this);
        return $this;
    }

    /**
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    /**
     * Enable debug output.
     * You need to setter inject a custom handler implementing the DebugOutputHandlerInterface via
     * Unzer::setDebugHandler() for this to work.
     *
     * @param bool $debugMode
     *
     * @return Unzer
     */
    public function setDebugMode(bool $debugMode): Unzer
    {
        $this->debugMode = $debugMode;
        return $this;
    }

    /**
     * @return DebugHandlerInterface|null
     */
    public function getDebugHandler(): ?DebugHandlerInterface
    {
        return $this->debugHandler;
    }

    /**
     * Use this method to inject a custom handler for debug messages from the http-adapter.
     * Remember to enable debug output using Unzer::setDebugMode(true).
     *
     * @param DebugHandlerInterface $debugHandler
     *
     * @return Unzer
     */
    public function setDebugHandler(DebugHandlerInterface $debugHandler): Unzer
    {
        $this->debugHandler = $debugHandler;
        return $this;
    }

    /**
     * @return HttpService
     */
    public function getHttpService(): HttpService
    {
        return $this->httpService;
    }

    /**
     * @param HttpService $httpService
     *
     * @return Unzer
     */
    public function setHttpService(HttpService $httpService): Unzer
    {
        $this->httpService = $httpService;
        return $this;
    }

    /**
     * Returns this Unzer instance.
     *
     * @return Unzer This Unzer object.
     */
    public function getUnzerObject(): Unzer
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getUri(bool $appendId = true, string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function activateRecurringPayment($paymentType, string $returnUrl, string $recurrenceType = null): Recurring
    {
        return $this->resourceService->activateRecurringPayment($paymentType, $returnUrl, $recurrenceType);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchPayPage($payPage): Paypage
    {
        return $this->resourceService->fetchPayPage($payPage);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchPayment($payment): Payment
    {
        return $this->resourceService->fetchPayment($payment);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchPaymentByOrderId(string $orderId): Payment
    {
        return $this->resourceService->fetchPaymentByOrderId($orderId);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchKeypair(bool $detailed = false): Keypair
    {
        return $this->resourceService->fetchKeypair($detailed);
    }

    /**
     * {@inheritDoc}
     */
    public function createMetadata(Metadata $metadata): Metadata
    {
        return $this->resourceService->createMetadata($metadata);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchMetadata($metadata): Metadata
    {
        return $this->resourceService->fetchMetadata($metadata);
    }

    /**
     * {@inheritDoc}
     */
    public function createBasket(Basket $basket): Basket
    {
        return $this->resourceService->createBasket($basket);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchBasket($basket): Basket
    {
        return $this->resourceService->fetchBasket($basket);
    }

    /**
     * {@inheritDoc}
     */
    public function updateBasket(Basket $basket): Basket
    {
        return $this->resourceService->updateBasket($basket);
    }

    /**
     * {@inheritDoc}
     */
    public function createPaymentType(BasePaymentType $paymentType): BasePaymentType
    {
        return $this->resourceService->createPaymentType($paymentType);
    }

    /**
     * {@inheritDoc}
     */
    public function updatePaymentType(BasePaymentType $paymentType): BasePaymentType
    {
        return $this->resourceService->updatePaymentType($paymentType);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchPaymentType(string $typeId): BasePaymentType
    {
        return $this->resourceService->fetchPaymentType($typeId);
    }

    /**
     * {@inheritDoc}
     */
    public function createCustomer(Customer $customer): Customer
    {
        return $this->resourceService->createCustomer($customer);
    }

    /**
     * {@inheritDoc}
     */
    public function createOrUpdateCustomer(Customer $customer): Customer
    {
        return $this->resourceService->createOrUpdateCustomer($customer);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchCustomer($customer): Customer
    {
        return $this->resourceService->fetchCustomer($customer);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchCustomerByExtCustomerId(string $customerId): Customer
    {
        return $this->resourceService->fetchCustomerByExtCustomerId($customerId);
    }

    /**
     * {@inheritDoc}
     */
    public function updateCustomer(Customer $customer): Customer
    {
        return $this->resourceService->updateCustomer($customer);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteCustomer($customer): void
    {
        $this->resourceService->deleteCustomer($customer);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAuthorization($payment): Authorization
    {
        return $this->resourceService->fetchAuthorization($payment);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchChargeById($payment, string $chargeId): Charge
    {
        return $this->resourceService->fetchChargeById($payment, $chargeId);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchCharge(Charge $charge): Charge
    {
        return $this->resourceService->fetchCharge($charge);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchChargebackById(string $paymentId, string $charebackId, ?string $chargeId): Chargeback
    {
        return $this->resourceService->fetchChargebackById($paymentId, $charebackId, $chargeId);
    }

    public function fetchChargeback(Chargeback $chargeback): Chargeback
    {
        return $this->resourceService->fetchResource($chargeback);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchReversalByAuthorization(Authorization $authorization, string $cancellationId): Cancellation
    {
        return $this->resourceService->fetchReversalByAuthorization($authorization, $cancellationId);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchReversal($payment, string $cancellationId): Cancellation
    {
        return $this->resourceService->fetchReversal($payment, $cancellationId);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchRefundById($payment, string $chargeId, string $cancellationId): Cancellation
    {
        return $this->resourceService->fetchRefundById($payment, $chargeId, $cancellationId);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchRefund(Charge $charge, string $cancellationId): Cancellation
    {
        return $this->resourceService->fetchRefund($charge, $cancellationId);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchPaymentRefund($payment, string $cancellationId): Cancellation
    {
        return $this->resourceService->fetchPaymentRefund($payment, $cancellationId);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchPaymentReversal($payment, string $cancellationId): Cancellation
    {
        return $this->resourceService->fetchPaymentReversal($payment, $cancellationId);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchShipment($payment, string $shipmentId): Shipment
    {
        return $this->resourceService->fetchShipment($payment, $shipmentId);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchPayout($payment): Payout
    {
        return $this->resourceService->fetchPayout($payment);
    }

    /**
     * {@inheritDoc}
     */
    public function createWebhook(string $url, string $event): Webhook
    {
        return $this->webhookService->createWebhook($url, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchWebhook($webhook): Webhook
    {
        return $this->webhookService->fetchWebhook($webhook);
    }

    /**
     * {@inheritDoc}
     */
    public function updateWebhook(Webhook $webhook): Webhook
    {
        return $this->webhookService->updateWebhook($webhook);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteWebhook($webhook)
    {
        return $this->webhookService->deleteWebhook($webhook);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllWebhooks(): array
    {
        return $this->webhookService->fetchAllWebhooks();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAllWebhooks(): void
    {
        $this->webhookService->deleteAllWebhooks();
    }

    /**
     * {@inheritDoc}
     */
    public function registerMultipleWebhooks(string $url, array $events): array
    {
        return $this->webhookService->registerMultipleWebhooks($url, $events);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchResourceFromEvent(string $eventJson = null): AbstractUnzerResource
    {
        return $this->webhookService->fetchResourceFromEvent($eventJson);
    }

    /**
     * {@inheritDoc}
     */
    public function performAuthorization(
        Authorization $authorization,
        $paymentType,
        $customer = null,
        Metadata      $metadata = null,
        Basket        $basket = null
    ): Authorization
    {
        return $this->paymentService->performAuthorization($authorization, $paymentType, $customer, $metadata, $basket);
    }

    /**
     * {@inheritDoc}
     */
    public function updateAuthorization($payment, Authorization $authorization): Authorization
    {
        return $this->paymentService->updateAuthorization($payment, $authorization);
    }

    /**
     * {@inheritDoc}
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
    ): Authorization
    {
        return $this->paymentService->authorize(
            $amount,
            $currency,
            $paymentType,
            $returnUrl,
            $customer,
            $orderId,
            $metadata,
            $basket,
            $card3ds,
            $invoiceId,
            $referenceText,
            $recurrenceType
        );
    }

    /**
     * {@inheritDoc}
     */
    public function performCharge(
        Charge   $charge,
        $paymentType,
        $customer = null,
        Metadata $metadata = null,
        Basket   $basket = null
    ): Charge
    {
        return $this->paymentService->performCharge($charge, $paymentType, $customer, $metadata, $basket);
    }

    public function updateCharge($payment, Charge $charge): Charge
    {
        return $this->paymentService->updateCharge($payment, $charge);
    }

    /**
     * {@inheritDoc}
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
    ): Charge
    {
        return $this->paymentService->charge(
            $amount,
            $currency,
            $paymentType,
            $returnUrl,
            $customer,
            $orderId,
            $metadata,
            $basket,
            $card3ds,
            $invoiceId,
            $paymentReference,
            $recurrenceType
        );
    }

    /**
     * {@inheritDoc}
     */
    public function chargeAuthorization(
        $payment,
        float $amount = null,
        string $orderId = null,
        string $invoiceId = null
    ): Charge
    {
        return $this->paymentService->chargeAuthorization($payment, $amount, $orderId, $invoiceId);
    }

    /**
     * {@inheritDoc}
     */
    public function chargePayment(
        $payment,
        float $amount = null,
        string $orderId = null,
        string $invoiceId = null
    ): Charge
    {
        return $this->paymentService->chargePayment($payment, $amount, $orderId, $invoiceId);
    }

    public function performChargeOnPayment($payment, Charge $charge): Charge
    {
        return $this->paymentService->performChargeOnPayment($payment, $charge);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelAuthorization(Authorization $authorization, float $amount = null): Cancellation
    {
        return $this->cancelService->cancelAuthorization($authorization, $amount);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelAuthorizationByPayment($payment, float $amount = null): Cancellation
    {
        return $this->cancelService->cancelAuthorizationByPayment($payment, $amount);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelPayment(
        $payment,
        float $amount = null,
        ?string $reasonCode = CancelReasonCodes::REASON_CODE_CANCEL,
        string $referenceText = null,
        float $amountNet = null,
        float $amountVat = null
    ): array
    {
        return $this->cancelService
            ->cancelPayment($payment, $amount, $reasonCode, $referenceText, $amountNet, $amountVat);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelPaymentAuthorization($payment, float $amount = null): ?Cancellation
    {
        return $this->cancelService->cancelPaymentAuthorization($payment, $amount);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelChargeById(
        $payment,
        string $chargeId,
        float $amount = null,
        string $reasonCode = null,
        string $referenceText = null,
        float $amountNet = null,
        float $amountVat = null
    ): Cancellation
    {
        return $this->cancelService
            ->cancelChargeById($payment, $chargeId, $amount, $reasonCode, $referenceText, $amountNet, $amountVat);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelCharge(
        Charge $charge,
        float  $amount = null,
        string $reasonCode = null,
        string $referenceText = null,
        float  $amountNet = null,
        float  $amountVat = null
    ): Cancellation
    {
        return $this->cancelService
            ->cancelCharge($charge, $amount, $reasonCode, $referenceText, $amountNet, $amountVat);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelAuthorizedPayment($payment, ?Cancellation $cancellation = null): Cancellation
    {
        return $this->cancelService
            ->cancelAuthorizedPayment($payment, $cancellation);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelChargedPayment($payment, ?Cancellation $cancellation = null): Cancellation
    {
        return $this->cancelService
            ->cancelChargedPayment($payment, $cancellation);
    }

    /**
     * Create a new Token resource containing a JWT token.
     * @throws UnzerApiException
     */
    public function createAuthToken(): Token
    {
        $token = (new Token())->setParentResource($this);
        $this->resourceService->createResource($token);
        return $token;
    }

    /**
     * {@inheritDoc}
     */
    public function ship($payment, string $invoiceId = null, string $orderId = null): Shipment
    {
        return $this->paymentService->ship($payment, $invoiceId, $orderId);
    }

    /**
     * {@inheritDoc}
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
    ): Payout
    {
        return $this->paymentService->payout(
            $amount,
            $currency,
            $paymentType,
            $returnUrl,
            $customer,
            $orderId,
            $metadata,
            $basket,
            $invoiceId,
            $referenceText
        );
    }

    /**
     * Create paypage v2 resource.
     */
    public function createPaypage(PaypageV2 $paypage): PaypageV2
    {
        return $this->resourceService->createPaypage($paypage);
    }

    /**
     * Delete paypage v2 resource.
     */
    public function deletePaypage(PaypageV2 $paypage): void
    {
        $this->resourceService->deletePaypage($paypage);
    }

    /**
     * Delete paypage v2 by resource ID.
     */
    public function deletePaypageById(string $paypageId): void
    {
        $paypage = new PaypageV2(null, '');
        $paypage->setId($paypageId);
        $this->deletePaypage($paypage);
    }

    /**
     * Update paypage v2 resource using patch method.
     */
    public function patchPaypage(PaypageV2 $paypage): PaypageV2
    {
        if ($paypage->getId() === null) {
            throw new RuntimeException('Paypage ID is required for patch operation.');
        }

        return $this->resourceService->updatePaypage($paypage);
    }

    /**
     * Fetch list of associated payments for the given payment page. Use `\UnzerSDK\Resources\V2\Paypage::getPayments`
     * to get the list of payments.
     */
    public function fetchPaypageV2($paypage): PaypageV2
    {
        return $this->resourceService->fetchPayPageV2($paypage);
    }

    /**
     * {@inheritDoc}
     */
    public function initPayPageCharge(
        Paypage  $paypage,
        Customer $customer = null,
        Basket   $basket = null,
        Metadata $metadata = null
    ): Paypage
    {
        return $this->paymentService->initPayPageCharge($paypage, $customer, $basket, $metadata);
    }

    /**
     * {@inheritDoc}
     */
    public function initPayPageAuthorize(
        Paypage  $paypage,
        Customer $customer = null,
        Basket   $basket = null,
        Metadata $metadata = null
    ): Paypage
    {
        return $this->paymentService->initPayPageAuthorize($paypage, $customer, $basket, $metadata);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchInstallmentPlans(
        float    $amount,
        string   $currency,
        float    $effectiveInterest,
        DateTime $orderDate = null
    ): InstalmentPlans
    {
        return $this->paymentService
            ->fetchInstallmentPlans($amount, $currency, $effectiveInterest, $orderDate);
    }

    public function fetchPaylaterInstallmentPlans(InstallmentPlansQuery $plansRequest): PaylaterInstallmentPlans
    {
        return $this->getPaymentService()->fetchPaylaterInstallmentPlans($plansRequest);
    }

    /**
     * {@inheritDoc}
     *
     * @param Config|null $config
     */
    public function fetchConfig(BasePaymentType $paymentType, ?Config $config = null): Config
    {
        return $this->getResourceService()->fetchConfig($paymentType, $config);
    }

    /**
     * Writes the given string to the registered debug handler if debug mode is enabled.
     *
     * @param $message
     */
    public function debugLog($message): void
    {
        if ($this->isDebugMode()) {
            $debugHandler = $this->getDebugHandler();
            if ($debugHandler instanceof DebugHandlerInterface) {
                $debugHandler->log('(' . getmypid() . ') ' . $message);
            }
        }
    }

    /**
     * Request a JWT token from the Token Service and stores it for following request.
     * If the token is already set, it will not be requested again.
     *
     * @throws UnzerApiException
     */
    public function prepareJwtToken()
    {
        if ($this->jwtToken !== null && JwtService::validateExpiryTime($this->jwtToken)) {
            return;
        }
        $this->jwtToken = $this->createAuthToken()->getAccessToken();
    }

    public function getJwtToken()
    {
        return $this->jwtToken;
    }
}
