<?php

namespace UnzerSDK\Resources\V2;

use DateTime;
use stdClass;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Apis\PaypageAPIConfig;
use UnzerSDK\Constants\PaypageCheckoutTypes;
use UnzerSDK\Constants\TransactionTypes;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\EmbeddedResources\Paypage\AmountSettings;
use UnzerSDK\Resources\EmbeddedResources\Paypage\Payment;
use UnzerSDK\Resources\EmbeddedResources\Paypage\PaymentMethodsConfigs;
use UnzerSDK\Resources\EmbeddedResources\Paypage\Resources;
use UnzerSDK\Resources\EmbeddedResources\Paypage\Style;
use UnzerSDK\Resources\EmbeddedResources\Paypage\Urls;
use UnzerSDK\Resources\EmbeddedResources\Risk;

class Paypage extends AbstractUnzerResource
{
    public const URI = '/merchant/paypage';

    protected static $keyClassMap = [
        'urls' => Urls::class,
        'style' => Style::class,
        'resources' => Resources::class,
        'risk' => Risk::class,
        'paymentMethodsConfigs' => PaymentMethodsConfigs::class,
        'amountSettings' => AmountSettings::class
    ];

    /** @var string|null checkoutType
     * @see PaypageCheckoutTypes
     */
    protected ?string $checkoutType = null;
    protected ?string $invoiceId = null;
    protected ?string $orderId = null;
    protected ?string $paymentReference = null;
    protected ?string $recurrenceType = null;
    protected ?string $shopName = null;
    protected ?string $type = null;
    protected ?float $amount = null;
    protected ?string $currency = null;

    /** @var string $mode "charge" or "authorize" */
    protected string $mode;

    protected ?Urls $urls = null;
    protected ?Style $style = null;
    protected ?Resources $resources = null;
    protected $paymentMethodsConfigs;
    protected ?Risk $risk = null;

    // Linkpay only.
    protected ?string $alias = null;
    protected ?bool $multiUse = null;

    protected ?DateTime $expiresAt = null;
    protected ?AmountSettings $amountSettings = null;

    // Response fields
    private ?string $redirectUrl = null;

    private ?array $payments = null;
    private ?int $total = null;

    private ?string $qrCodeSvg = null;

    /**
     * @param $amount
     * @param $currency
     * @param $mode
     */
    public function __construct(?float $amount = null, ?string $currency = null, ?string $mode = TransactionTypes::CHARGE)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->mode = $mode;
    }

    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        if (isset($response->paypageId) && $this->id === null) {
            $this->id = $response->paypageId;
        }

        if ($this->keyValueEsists('payments', $response)) {
            $payments = [];
            foreach ($response->payments as $payment) {
                $newPayment = (new Payment());
                $newPayment->handleResponse($payment);

                $payments[] = $newPayment;
            }
            $this->payments = $payments;
        }

        $expiresAtKey = 'expiresAt';
        if ($this->keyValueEsists($expiresAtKey, $response)) {
            $this->setExpiresAt(new DateTime($response->$expiresAtKey));
            unset($response->$expiresAtKey);
        }

        // Instantiate embedded objects.
        foreach (self::$keyClassMap as $key => $class) {
            if ($this->keyValueEsists($key, $response) && $this->hasProperties($response->$key)) {
                $object = new $class();
                $this->$key = $object;
            }
        }

        parent::handleResponse($response, $method);
    }


    /**
     * @return mixed
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     *
     * @return Paypage
     */
    public function setAmount($amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getApiConfig(): string
    {
        return PaypageAPIConfig::class;
    }

    public function getUri(bool $appendId = true, string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        $uri = [self::URI];
        if ($appendId) {
            if ($this->getId() !== null) {
                $uri[] = $this->getId();
            } elseif ($this->getExternalId() !== null) {
                $uri[] = $this->getExternalId();
            }
        }

        return implode('/', $uri);
    }

    public function getApiVersion(): string
    {
        return 'v2';
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): Paypage
    {
        $this->currency = $currency;
        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): Paypage
    {
        $this->mode = $mode;
        return $this;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function setRedirectUrl(string $redirectUrl): Paypage
    {
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): Paypage
    {
        $this->type = $type;
        return $this;
    }

    public function getRecurrenceType(): ?string
    {
        return $this->recurrenceType;
    }

    public function setRecurrenceType(?string $recurrenceType): Paypage
    {
        $this->recurrenceType = $recurrenceType;
        return $this;
    }

    public function getShopName(): ?string
    {
        return $this->shopName;
    }

    public function setShopName(?string $shopName): Paypage
    {
        $this->shopName = $shopName;
        return $this;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): Paypage
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(?string $invoiceId): Paypage
    {
        $this->invoiceId = $invoiceId;
        return $this;
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): Paypage
    {
        $this->paymentReference = $paymentReference;
        return $this;
    }

    public function getUrls(): ?Urls
    {
        return $this->urls;
    }

    public function setUrls(Urls $urls): Paypage
    {
        $this->urls = $urls;
        return $this;
    }

    public function getStyle(): ?Style
    {
        return $this->style;
    }

    public function setStyle(Style $style): Paypage
    {
        $this->style = $style;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getResources()
    {
        return $this->resources;
    }

    public function setResources(Resources $resources): Paypage
    {
        $this->resources = $resources;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPaymentMethodsConfigs(): ?array
    {
        return $this->paymentMethodsConfigs;
    }

    public function setPaymentMethodsConfigs(PaymentMethodsConfigs $paymentMethodsConfigs)
    {
        $this->paymentMethodsConfigs = $paymentMethodsConfigs;
        return $this;
    }

    public function getRisk(): ?Risk
    {
        return $this->risk;
    }

    public function setRisk(Risk $risk): Paypage
    {
        $this->risk = $risk;
        return $this;
    }

    /**
     * @return Payment[]|null
     */
    public function getPayments(): ?array
    {
        return $this->payments;
    }

    public function setPayments(?array $payments): Paypage
    {
        $this->payments = $payments;
        return $this;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(?int $total): Paypage
    {
        $this->total = $total;
        return $this;
    }

    public function getCheckoutType(): ?string
    {
        return $this->checkoutType;
    }

    /** @see PaypageCheckoutTypes for available types. */
    public function setCheckoutType(?string $checkoutType): Paypage
    {
        $this->checkoutType = $checkoutType;
        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): Paypage
    {
        $this->alias = $alias;
        return $this;
    }

    public function getMultiUse(): ?bool
    {
        return $this->multiUse;
    }

    public function setMultiUse(?bool $multiUse): Paypage
    {
        $this->multiUse = $multiUse;
        return $this;
    }

    public function getExpiresAt(): ?DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?DateTime $expiresAt): Paypage
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getAmountSettings(): ?AmountSettings
    {
        return $this->amountSettings;
    }

    public function setAmountSettings(?AmountSettings $amountSettings): Paypage
    {
        $this->amountSettings = $amountSettings;
        return $this;
    }

    public function getQrCodeSvg(): ?string
    {
        return $this->qrCodeSvg;
    }

    public function setQrCodeSvg(?string $qrCodeSvg): Paypage
    {
        $this->qrCodeSvg = $qrCodeSvg;
        return $this;
    }


    public function expose()
    {
        $exposeArray = parent::expose();
        $expiresAtKey = 'expiresAt';
        if (isset($exposeArray[$expiresAtKey])) {
            $exposeArray['expiresAt'] = $this->getExpiresAt()->format(DateTime::ATOM);
        }
        return $exposeArray;
    }

    /**
     * @param string $key
     * @param stdClass $response
     * @return bool
     */
    public function keyValueEsists(string $key, stdClass $response): bool
    {
        return isset($response->$key) && !empty($response->$key);
    }

    protected function hasProperties(stdClass $object)
    {
        return count(get_object_vars($object)) > 0;
    }

}
