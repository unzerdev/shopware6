<?php
/**
 * This is the base class for all transaction types.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Resources\TransactionTypes;

use RuntimeException;
use stdClass;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\EmbeddedResources\CardTransactionData;
use UnzerSDK\Resources\EmbeddedResources\RiskData;
use UnzerSDK\Resources\EmbeddedResources\ShippingData;
use UnzerSDK\Resources\EmbeddedResources\WeroTransactionData;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Traits\HasAdditionalTransactionData;
use UnzerSDK\Traits\HasCustomerMessage;
use UnzerSDK\Traits\HasDate;
use UnzerSDK\Traits\HasInvoiceId;
use UnzerSDK\Traits\HasOrderId;
use UnzerSDK\Traits\HasStates;
use UnzerSDK\Traits\HasTraceId;
use UnzerSDK\Traits\HasUniqueAndShortId;

abstract class AbstractTransactionType extends AbstractUnzerResource
{
    use HasOrderId;
    use HasInvoiceId;
    use HasStates;
    use HasUniqueAndShortId;
    use HasTraceId;
    use HasCustomerMessage;
    use HasAdditionalTransactionData;
    use HasDate;


    /** @var Payment $payment */
    private $payment;

    /**
     * Return the payment property.
     *
     * @return Payment|null
     */
    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    /**
     * Set the payment object property.
     *
     * @param Payment $payment
     *
     * @return $this
     */
    public function setPayment(Payment $payment): self
    {
        $this->payment = $payment;
        $this->setParentResource($payment);
        return $this;
    }

    /**
     * Return the ID of the referenced payment object.
     *
     * @return null|string The ID of the payment object or null if nothing is found.
     */
    public function getPaymentId(): ?string
    {
        if ($this->payment instanceof Payment) {
            return $this->payment->getId();
        }

        return null;
    }

    /**
     * Return the redirect url stored in the payment object.
     *
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->payment->getRedirectUrl();
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

        /** @var Payment $payment */
        $payment = $this->getPayment();
        if (isset($response->resources->paymentId)) {
            $payment->setId($response->resources->paymentId);
        }

        if (isset($response->redirectUrl)) {
            $payment->handleResponse((object)['redirectUrl' => $response->redirectUrl]);
        }

        $this->handleAdditionalTransactionData($response);

        if ($method !== HttpAdapterInterface::REQUEST_GET) {
            $this->fetchPayment();
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException
     */
    public function getLinkedResources(): array
    {
        /** @var Payment $payment */
        $payment = $this->getPayment();
        $paymentType = $payment ? $payment->getPaymentType() : null;
        if (!$paymentType instanceof BasePaymentType) {
            throw new RuntimeException('Payment type is missing!');
        }

        return [
            'customer' => $payment->getCustomer(),
            'type' => $paymentType,
            'metadata' => $payment->getMetadata(),
            'basket' => $payment->getBasket()
        ];
    }

    /**
     * Updates the referenced payment object if it exists and if this is not the payment object itself.
     * This is called from the crud methods to update the payments state whenever anything happens.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchPayment(): void
    {
        $payment = $this->getPayment();
        if ($payment instanceof AbstractUnzerResource) {
            $this->fetchResource($payment);
        }
    }

    /**
     * Handle additional transaction data from API response.
     *
     * @param stdClass $response
     *
     * @return void
     */
    protected function handleAdditionalTransactionData(stdClass $response): void
    {
        $additionalTransactionData = $response->additionalTransactionData ?? null;
        if ($additionalTransactionData !== null) {
            $this->setAdditionalTransactionData($additionalTransactionData);

            $this->handleRiskData($additionalTransactionData);
            $this->handleShipping($additionalTransactionData);
            $this->handleCardTransactionData($additionalTransactionData);
            $this->handleWeroTransactionData($additionalTransactionData);
        }
    }

    /**
     * Handle risk data object contained in additional transaction data from API response.
     *
     * @param stdClass $additionalTransactionData
     *
     * @return void
     */
    protected function handleRiskData(stdClass $additionalTransactionData): void
    {
        $riskData = $additionalTransactionData->riskData ?? null;
        if ($riskData !== null) {
            $riskDataObject = $this->getRiskData() ?? new RiskData();
            $riskDataObject->handleResponse($riskData);
            $this->setRiskData($riskDataObject);
        }
    }

    /**
     * Handle risk data object contained in additional transaction data from API response.
     *
     * @param stdClass $additionalTransactionData
     *
     * @return void
     */
    protected function handleShipping(stdClass $additionalTransactionData): void
    {
        $shipping = $additionalTransactionData->shipping ?? null;
        if ($shipping !== null) {
            $shippingObject = $this->getShipping() ?? new ShippingData();
            $shippingObject->handleResponse($shipping);
            $this->setShipping($shippingObject);
        }
    }

    /**
     * Handle CardTransactionData object contained in additional transaction data from API response.
     *
     * @param stdClass $additionalTransactionData
     *
     * @return void
     */
    protected function handleCardTransactionData(stdClass $additionalTransactionData): void
    {
        $card = $additionalTransactionData->card ?? null;
        if ($card !== null) {
            $cardTransactionData = $this->getCardTransactionData() ?? new CardTransactionData();
            $cardTransactionData->handleResponse($card);
            $this->setCardTransactionData($cardTransactionData);
        }
    }

    /**
     * Handle WeroTransactionData object contained in additional transaction data from API response.
     *
     * @param stdClass $additionalTransactionData
     *
     * @return void
     */
    protected function handleWeroTransactionData(stdClass $additionalTransactionData): void
    {
        $wero = $additionalTransactionData->wero ?? null;
        if ($wero !== null) {
            $weroTransactionData = $this->getWeroTransactionData() ?? new WeroTransactionData();
            $weroTransactionData->handleResponse($wero);
            $this->setWeroTransactionData($weroTransactionData);
        }
    }
}
