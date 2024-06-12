<?php

namespace UnzerSDK\Services;

use DateTime;
use UnzerSDK\Constants\TransactionTypes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\EmbeddedResources\Paylater\InstallmentPlansQuery;
use UnzerSDK\Resources\PaylaterInstallmentPlans;
use UnzerSDK\Resources\PaymentTypes\PaylaterInstallment;
use UnzerSDK\Unzer;
use UnzerSDK\Interfaces\PaymentServiceInterface;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\InstalmentPlans;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Resources\PaymentTypes\Paypage;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Payout;
use UnzerSDK\Resources\TransactionTypes\Shipment;
use RuntimeException;

/**
 * This service provides for functionalities concerning payment transactions.
 *
 * @link  https://docs.unzer.com/
 *
 */
class PaymentService implements PaymentServiceInterface
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
        $this->unzer       = $unzer;
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
     * @return PaymentService
     */
    public function setUnzer(Unzer $unzer): PaymentService
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

    public function performAuthorization(
        Authorization $authorization,
        $paymentType,
        $customer = null,
        Metadata $metadata = null,
        Basket $basket = null
    ): Authorization {
        $payment = $this->createPayment($paymentType);
        $paymentType = $payment->getPaymentType();
        $authorization->setSpecialParams($paymentType !== null ? $paymentType->getTransactionParams() : []);

        $payment->setAuthorization($authorization)->setCustomer($customer)->setMetadata($metadata)->setBasket($basket);

        $this->getResourceService()->createResource($authorization);
        return $authorization;
    }

    /**
     * {@inheritDoc}
     *
     * @param Authorization $payment
     */
    public function updateAuthorization($payment, Authorization $authorization): Authorization
    {
        $authorization->setId(null);
        $paymentResource = $this->getResourceService()->getPaymentResource($payment);
        $authorization->setPayment($paymentResource);
        $this->getResourceService()->patchResource($authorization);
        return $authorization;
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
    ): Authorization {
        $payment = $this->createPayment($paymentType);
        $paymentType = $payment->getPaymentType();

        /** @var Authorization $authorization */
        $authorization = (new Authorization($amount, $currency, $returnUrl))
            ->setOrderId($orderId)
            ->setInvoiceId($invoiceId)
            ->setPaymentReference($referenceText);
        if ($card3ds !== null) {
            $authorization->setCard3ds($card3ds);
        }
        $payment->setAuthorization($authorization)->setCustomer($customer)->setMetadata($metadata)->setBasket($basket);

        if ($recurrenceType !== null) {
            $authorization->setRecurrenceType($recurrenceType);
        }
        $this->performAuthorization($authorization, $paymentType, $customer, $metadata, $basket);
        return $authorization;
    }

    /**
     * {@inheritDoc}
     */
    public function performCharge(Charge $charge, $paymentType, $customer = null, Metadata $metadata = null, Basket $basket = null): Charge
    {
        $payment     = $this->createPayment($paymentType);
        $paymentType = $payment->getPaymentType();

        /** @var Charge $charge */
        $charge->setSpecialParams($paymentType->getTransactionParams() ?? []);
        $payment->addCharge($charge)->setCustomer($customer)->setMetadata($metadata)->setBasket($basket);

        $this->getResourceService()->createResource($charge);

        return $charge;
    }

    /**
     * {@inheritDoc}
     *
     * @param Charge $payment
     */
    public function updateCharge($payment, Charge $charge): Charge
    {
        $charge->setId(null);
        $paymentResource = $this->getResourceService()->getPaymentResource($payment);
        $charge->setPayment($paymentResource);
        $this->getResourceService()->patchResource($charge);
        return $charge;
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
    ): Charge {
        $payment     = $this->createPayment($paymentType);
        $paymentType = $payment->getPaymentType();

        /** @var Charge $charge */
        $charge = (new Charge($amount, $currency, $returnUrl))
            ->setOrderId($orderId)
            ->setInvoiceId($invoiceId)
            ->setPaymentReference($paymentReference);
        if ($card3ds !== null) {
            $charge->setCard3ds($card3ds);
        }
        $payment->addCharge($charge)->setCustomer($customer)->setMetadata($metadata)->setBasket($basket);

        if ($recurrenceType !== null) {
            $charge->setRecurrenceType($recurrenceType);
        }

        return $this->performCharge($charge, $paymentType, $customer, $metadata, $basket);
    }

    /**
     * {@inheritDoc}
     */
    public function chargeAuthorization(
        $payment,
        float $amount = null,
        string $orderId = null,
        string $invoiceId = null
    ): Charge {
        return $this->chargePayment($payment, $amount, $orderId, $invoiceId);
    }

    /**
     * {@inheritDoc}
     */
    public function chargePayment(
        $payment,
        float $amount = null,
        string $orderId = null,
        string $invoiceId = null
    ): Charge {
        $charge = new Charge($amount);

        if ($orderId !== null) {
            $charge->setOrderId($orderId);
        }
        if ($invoiceId !== null) {
            $charge->setInvoiceId($invoiceId);
        }

        return $this->performChargeOnPayment($payment, $charge);
    }

    /**
     * {@inheritDoc}
     */
    public function performChargeOnPayment($payment, Charge $charge): Charge
    {
        $paymentResource = $this->getResourceService()->getPaymentResource($payment);
        $paymentResource->addCharge($charge);
        $this->getResourceService()->createResource($charge);

        return $charge;
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
        string $referenceText = null
    ): Payout {
        $payment = $this->createPayment($paymentType);
        $payout = (new Payout($amount, $currency, $returnUrl))
            ->setOrderId($orderId)
            ->setInvoiceId($invoiceId)
            ->setPaymentReference($referenceText);
        $payment->setPayout($payout)->setCustomer($customer)->setMetadata($metadata)->setBasket($basket);
        $this->getResourceService()->createResource($payout);

        return $payout;
    }

    /**
     * {@inheritDoc}
     */
    public function ship($payment, string $invoiceId = null, string $orderId = null): Shipment
    {
        $shipment = new Shipment();
        $shipment->setInvoiceId($invoiceId)->setOrderId($orderId);
        $this->getResourceService()->getPaymentResource($payment)->addShipment($shipment);
        $this->getResourceService()->createResource($shipment);
        return $shipment;
    }

    /**
     * {@inheritDoc}
     */
    public function initPayPageCharge(
        Paypage $paypage,
        Customer $customer = null,
        Basket $basket = null,
        Metadata $metadata = null
    ): Paypage {
        return $this->initPayPage($paypage, TransactionTypes::CHARGE, $customer, $basket, $metadata);
    }

    /**
     * {@inheritDoc}
     */
    public function initPayPageAuthorize(
        Paypage $paypage,
        Customer $customer = null,
        Basket $basket = null,
        Metadata $metadata = null
    ): Paypage {
        return $this->initPayPage($paypage, TransactionTypes::AUTHORIZATION, $customer, $basket, $metadata);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchInstallmentPlans(
        float    $amount,
        string   $currency,
        float    $effectiveInterest,
        DateTime $orderDate = null
    ): InstalmentPlans {
        $ins   = (new InstallmentSecured(null, null, null))->setParentResource($this->unzer);
        $plans = (new InstalmentPlans($amount, $currency, $effectiveInterest, $orderDate))->setParentResource($ins);
        /** @var InstalmentPlans $plans */
        $plans = $this->unzer->getResourceService()->fetchResource($plans);
        return $plans;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchPaylaterInstallmentPlans(
        InstallmentPlansQuery $paylaterInstallmentPlansQuery
    ): PaylaterInstallmentPlans {
        $paylaterInstallment = (new PaylaterInstallment(null, null, null, null))->setParentResource($this->unzer);
        $plans = (new PaylaterInstallmentPlans())->setQueryParameter($paylaterInstallmentPlansQuery)->setParentResource($paylaterInstallment);
        return $this->unzer->getResourceService()->fetchResource($plans);
    }

    /**
     * Creates the PayPage for the requested transaction method.
     *
     * @param Paypage              $paypage  The PayPage resource to initialize.
     * @param string               $action   The transaction type (Charge or Authorize) to create the PayPage for.
     *                                       Depending on the chosen transaction the payment types available will vary.
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
    private function initPayPage(
        Paypage  $paypage,
        string   $action,
        Customer $customer = null,
        Basket   $basket = null,
        Metadata $metadata = null
    ): Paypage {
        $paypage->setAction($action)->setParentResource($this->unzer);
        $payment = $this->createPayment($paypage)->setBasket($basket)->setCustomer($customer)->setMetadata($metadata)->setPayPage($paypage);
        $this->getResourceService()->createResource($paypage->setPayment($payment));
        return $paypage;
    }

    /**
     * Create a Payment object with the given properties.
     *
     * @param BasePaymentType|string $paymentType
     *
     * @return Payment The resulting Payment object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    private function createPayment($paymentType): AbstractUnzerResource
    {
        return (new Payment($this->unzer))->setPaymentType($paymentType);
    }
}
