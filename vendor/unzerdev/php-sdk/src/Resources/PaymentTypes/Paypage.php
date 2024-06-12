<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Constants\AdditionalAttributes;
use UnzerSDK\Constants\ExemptionType;
use UnzerSDK\Constants\RecurrenceTypes;
use UnzerSDK\Constants\TransactionTypes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Traits\CanAuthorize;
use UnzerSDK\Traits\CanDirectCharge;
use UnzerSDK\Traits\HasInvoiceId;
use UnzerSDK\Traits\HasOrderId;
use RuntimeException;
use stdClass;

use function in_array;

/**
 * This is the implementation of the Pay Page which allows for displaying a page containing all
 * payment types of the merchant.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Paypage extends BasePaymentType
{
    use CanDirectCharge;
    use CanAuthorize;
    use HasInvoiceId;
    use HasOrderId;

    /** @var float $amount */
    protected $amount;

    /** @var string $currency*/
    protected $currency;

    /** @var string $returnUrl*/
    protected $returnUrl;

    /** @var string $logoImage */
    protected $logoImage;

    /** @var string $fullPageImage */
    protected $fullPageImage;

    /** @var string $shopName */
    protected $shopName;

    /** @var string $shopDescription */
    protected $shopDescription;

    /** @var string $tagline */
    protected $tagline;

    /** @var string $termsAndConditionUrl */
    protected $termsAndConditionUrl;

    /** @var string $privacyPolicyUrl */
    protected $privacyPolicyUrl;

    /** @var string $imprintUrl */
    protected $imprintUrl;

    /** @var string $helpUrl */
    protected $helpUrl;

    /** @var string $contactUrl */
    protected $contactUrl;

    /** @var String $action */
    private $action = TransactionTypes::CHARGE;

    /** @var Payment|null $payment */
    private $payment;

    /** @var string[] $excludeTypes */
    protected $excludeTypes = [];

    /** @var bool $card3ds */
    protected $card3ds;

    /** @var array|null $css */
    protected $css;

    /**
     * Paypage constructor.
     *
     * @param float  $amount
     * @param string $currency
     * @param string $returnUrl
     */
    public function __construct(float $amount, string $currency, string $returnUrl)
    {
        $this->setAmount($amount);
        $this->setCurrency($currency);
        $this->setReturnUrl($returnUrl);
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     *
     * @return Paypage
     */
    public function setAmount(float $amount): Paypage
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return Paypage
     */
    public function setCurrency(string $currency): Paypage
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return string
     */
    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    /**
     * @param string $returnUrl
     *
     * @return Paypage
     */
    public function setReturnUrl(string $returnUrl): Paypage
    {
        $this->returnUrl = $returnUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLogoImage(): ?string
    {
        return $this->logoImage;
    }

    /**
     * @param string|null $logoImage
     *
     * @return Paypage
     */
    public function setLogoImage(?string $logoImage): Paypage
    {
        $this->logoImage = $logoImage;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFullPageImage(): ?string
    {
        return $this->fullPageImage;
    }

    /**
     * @param string|null $fullPageImage
     *
     * @return Paypage
     */
    public function setFullPageImage(?string $fullPageImage): Paypage
    {
        $this->fullPageImage = $fullPageImage;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShopName(): ?string
    {
        return $this->shopName;
    }

    /**
     * @param string|null $shopName
     *
     * @return Paypage
     */
    public function setShopName(?string $shopName): Paypage
    {
        $this->shopName = $shopName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShopDescription(): ?string
    {
        return $this->shopDescription;
    }

    /**
     * @param string|null $shopDescription
     *
     * @return Paypage
     */
    public function setShopDescription(?string $shopDescription): Paypage
    {
        $this->shopDescription = $shopDescription;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTagline(): ?string
    {
        return $this->tagline;
    }

    /**
     * @param string|null $tagline
     *
     * @return Paypage
     */
    public function setTagline(?string $tagline): Paypage
    {
        $this->tagline = $tagline;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTermsAndConditionUrl(): ?string
    {
        return $this->termsAndConditionUrl;
    }

    /**
     * @param string|null $termsAndConditionUrl
     *
     * @return Paypage
     */
    public function setTermsAndConditionUrl(?string $termsAndConditionUrl): Paypage
    {
        $this->termsAndConditionUrl = $termsAndConditionUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPrivacyPolicyUrl(): ?string
    {
        return $this->privacyPolicyUrl;
    }

    /**
     * @param string|null $privacyPolicyUrl
     *
     * @return Paypage
     */
    public function setPrivacyPolicyUrl(?string $privacyPolicyUrl): Paypage
    {
        $this->privacyPolicyUrl = $privacyPolicyUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getImprintUrl(): ?string
    {
        return $this->imprintUrl;
    }

    /**
     * @param string|null $imprintUrl
     *
     * @return Paypage
     */
    public function setImprintUrl(?string $imprintUrl): Paypage
    {
        $this->imprintUrl = $imprintUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHelpUrl(): ?string
    {
        return $this->helpUrl;
    }

    /**
     * @param string|null $helpUrl
     *
     * @return Paypage
     */
    public function setHelpUrl(?string $helpUrl): Paypage
    {
        $this->helpUrl = $helpUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getContactUrl(): ?string
    {
        return $this->contactUrl;
    }

    /**
     * @param string|null $contactUrl
     *
     * @return Paypage
     */
    public function setContactUrl(?string $contactUrl): Paypage
    {
        $this->contactUrl = $contactUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param String $action
     *
     * @return Paypage
     */
    public function setAction(String $action): Paypage
    {
        $action = strtolower($action);
        if (in_array($action, [TransactionTypes::CHARGE, TransactionTypes::AUTHORIZATION], true)) {
            $this->action = $action;
        }

        return $this;
    }

    /**
     * @return Payment|null
     */
    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    /**
     * @param Payment $payment
     *
     * @return Paypage
     */
    public function setPayment(Payment $payment): Paypage
    {
        $this->payment = $payment;
        return $this;
    }

    /**
     * @return Basket|null
     */
    public function getBasket(): ?Basket
    {
        if (!$this->payment instanceof Payment) {
            return null;
        }
        return $this->payment->getBasket();
    }

    /**
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        if (!$this->payment instanceof Payment) {
            return null;
        }
        return $this->payment->getCustomer();
    }

    /**
     * @return Metadata|null
     */
    public function getMetadata(): ?Metadata
    {
        if (!$this->payment instanceof Payment) {
            return null;
        }
        return $this->payment->getMetadata();
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        $payment = $this->getPayment();
        if ($payment instanceof Payment) {
            return $payment->getRedirectUrl();
        }
        return null;
    }

    /**
     * @param string $redirectUrl
     *
     * @return Paypage
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function setRedirectUrl(string $redirectUrl): Paypage
    {
        $payment = $this->getPayment();
        if ($payment instanceof Payment) {
            $payment->handleResponse((object)['redirectUrl' => $redirectUrl]);
        }
        return $this;
    }

    /**
     * Return the Id of the referenced payment object.
     *
     * @return null|string The Id of the payment object or null if nothing is found.
     */
    public function getPaymentId(): ?string
    {
        if ($this->payment instanceof Payment) {
            return $this->payment->getId();
        }

        return null;
    }

    /**
     * Returns an array of payment types not shown on the paypage.
     *
     * @return string[]
     */
    public function getExcludeTypes(): array
    {
        return $this->excludeTypes;
    }

    /**
     * Sets array of payment types not shown on the paypage.
     *
     * @param string[] $excludeTypes
     *
     * @return Paypage
     */
    public function setExcludeTypes(array $excludeTypes): Paypage
    {
        $this->excludeTypes = $excludeTypes;
        return $this;
    }

    /**
     * Adds a payment type to the array of excluded payment types.
     *
     * @param string $excludeType The API name of the payment type resource that should not be shown on the paypage.
     *                            It can be retrieved by calling the static function `getResourceName` on the payment
     *                            type class e.g. Card::getResourceName().
     *
     * @return Paypage
     */
    public function addExcludeType(string $excludeType): Paypage
    {
        $this->excludeTypes[] = $excludeType;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isCard3ds(): ?bool
    {
        return $this->card3ds;
    }

    /**
     * @param bool|null $card3ds
     *
     * @return Paypage
     */
    public function setCard3ds(?bool $card3ds): Paypage
    {
        $this->card3ds = $card3ds;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getCss(): ?array
    {
        return $this->css;
    }

    /**
     * @param array|null $styles
     *
     * @return Paypage
     */
    public function setCss(?array $styles): Paypage
    {
        $this->css = empty($styles) ? null : $styles;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getEffectiveInterestRate(): ?float
    {
        return $this->getAdditionalAttribute(AdditionalAttributes::EFFECTIVE_INTEREST_RATE);
    }

    /**
     * @param float $effectiveInterestRate
     *
     * @return Paypage
     */
    public function setEffectiveInterestRate(float $effectiveInterestRate): Paypage
    {
        $this->setAdditionalAttribute(AdditionalAttributes::EFFECTIVE_INTEREST_RATE, $effectiveInterestRate);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRecurrenceType(): ?string
    {
        return $this->getAdditionalAttribute(AdditionalAttributes::RECURRENCE_TYPE);
    }

    /**
     * @param string $recurrenceType
     *
     * @see RecurrenceTypes
     *
     * @return Paypage
     */
    public function setRecurrenceType(string $recurrenceType): Paypage
    {
        $this->setAdditionalAttribute(AdditionalAttributes::RECURRENCE_TYPE, $recurrenceType);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getExemptionType(): ?string
    {
        return $this->getAdditionalAttribute(AdditionalAttributes::EXEMPTION_TYPE);
    }

    /**
     * @param string $exemptionType
     *
     * @see ExemptionType
     *
     * @return Paypage
     */
    public function setExemptionType(string $exemptionType): Paypage
    {
        $this->setAdditionalAttribute(AdditionalAttributes::EXEMPTION_TYPE, $exemptionType);
        return $this;
    }

    /**
     * {@inheritDoc}
     * Change resource path.
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        $basePath = 'paypage';

        if ($httpMethod === HttpAdapterInterface::REQUEST_GET) {
            return $basePath;
        }

        switch ($this->action) {
            case TransactionTypes::AUTHORIZATION:
                $transactionType = TransactionTypes::AUTHORIZATION;
                break;
            case TransactionTypes::CHARGE:
                // intended Fall-Through
            default:
                $transactionType = TransactionTypes::CHARGE;
                break;
        }

        return $basePath . '/' . $transactionType;
    }

    /**
     * {@inheritDoc}
     * Map external name of property to internal name of property.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        if (isset($response->impressumUrl)) {
            $response->imprintUrl = $response->impressumUrl;
            unset($response->impressumUrl);
        }

        /** @var Payment $payment */
        $payment = $this->getPayment();
        if (isset($response->resources->paymentId)) {
            $paymentId = $response->resources->paymentId;

            if (null === $payment) {
                $payment = new Payment($this->getUnzerObject());
                $payment->setId($paymentId)
                    ->setPayPage($this);
                $this->setPayment($payment);
                $this->fetchPayment();
            }

            $payment->setId($paymentId);
        }

        parent::handleResponse($response, $method);

        if (isset($response->additionalAttributes)) {
            $this->additionalAttributes = (array)$response->additionalAttributes;
        }

        if ($method !== HttpAdapterInterface::REQUEST_GET) {
            $this->fetchPayment();
        }
    }

    /**
     * {@inheritDoc}
     * Map external name of property to internal name of property.
     */
    public function expose()
    {
        $exposeArray = parent::expose();
        if (isset($exposeArray['imprintUrl'])) {
            $exposeArray['impressumUrl'] = $exposeArray['imprintUrl'];
            unset($exposeArray['imprintUrl']);
        }

        return $exposeArray;
    }

    /**
     * {@inheritDoc}
     */
    public function getLinkedResources(): array
    {
        return [
            'customer' => $this->getCustomer(),
            'metadata' => $this->getMetadata(),
            'basket' => $this->getBasket(),
            'payment' => $this->getPayment()
        ];
    }

    /**
     * Updates the referenced payment object if it exists and if this is not the payment object itself.
     * This is called from the crud methods to update the payments state whenever anything happens.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    private function fetchPayment(): void
    {
        $payment = $this->getPayment();
        if ($payment instanceof AbstractUnzerResource) {
            $this->fetchResource($payment);
        }
    }
}
