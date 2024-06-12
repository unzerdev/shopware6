<?php

namespace UnzerSDK\Resources;

use DateTime;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Traits\CanAuthorizeWithCustomer;
use stdClass;

/**
 * Resource representing the installment plan for Installment Secured.
 *
 * @link  https://docs.unzer.com/
 *
 */
class InstalmentPlan extends BasePaymentType
{
    use CanAuthorizeWithCustomer;

    /** @var string $orderDate */
    protected $orderDate;

    /** @var int $numberOfRates */
    protected $numberOfRates;

    /** @var string $dayOfPurchase */
    protected $dayOfPurchase;

    /** @var float $totalPurchaseAmount*/
    protected $totalPurchaseAmount;

    /** @var float $totalInterestAmount */
    protected $totalInterestAmount;

    /** @var float $totalAmount */
    protected $totalAmount;

    /** @var float $effectiveInterestRate */
    protected $effectiveInterestRate;

    /** @var float $nominalInterestRate */
    protected $nominalInterestRate;

    /** @var float $feeFirstRate */
    protected $feeFirstRate;

    /** @var float $feePerRate */
    protected $feePerRate;

    /** @var float $monthlyRate */
    protected $monthlyRate;

    /** @var float $lastRate */
    protected $lastRate;

    /** @var string $invoiceDate */
    protected $invoiceDate;

    /** @var string $invoiceDueDate */
    protected $invoiceDueDate;

    /** @var stdClass[]|null $installmentRates */
    private $installmentRates;

    /**
     * @param int|null    $numberOfRates
     * @param string|null $dayOfPurchase
     * @param float|null  $totalPurchaseAmount
     * @param float|null  $totalInterestAmount
     * @param float|null  $totalAmount
     * @param float|null  $effectiveInterestRate
     * @param float|null  $nominalInterestRate
     * @param float|null  $feeFirstRate
     * @param float|null  $feePerRate
     * @param float|null  $monthlyRate
     * @param float|null  $lastRate
     */
    public function __construct(
        int    $numberOfRates = null,
        string $dayOfPurchase = null,
        float  $totalPurchaseAmount = null,
        float  $totalInterestAmount = null,
        float  $totalAmount = null,
        float  $effectiveInterestRate = null,
        float  $nominalInterestRate = null,
        float  $feeFirstRate = null,
        float  $feePerRate = null,
        float  $monthlyRate = null,
        float  $lastRate = null
    ) {
        $this->numberOfRates         = $numberOfRates;
        $this->dayOfPurchase         = $dayOfPurchase;
        $this->totalPurchaseAmount   = $totalPurchaseAmount;
        $this->totalInterestAmount   = $totalInterestAmount;
        $this->totalAmount           = $totalAmount;
        $this->effectiveInterestRate = $effectiveInterestRate;
        $this->nominalInterestRate   = $nominalInterestRate;
        $this->feeFirstRate          = $feeFirstRate;
        $this->feePerRate            = $feePerRate;
        $this->monthlyRate           = $monthlyRate;
        $this->lastRate              = $lastRate;
    }

    /**
     * @return string|null
     */
    public function getOrderDate(): ?string
    {
        return $this->orderDate;
    }

    /**
     * @param DateTime|string|null $orderDate
     *
     * @return $this
     */
    public function setOrderDate($orderDate): self
    {
        $this->orderDate = $orderDate instanceof DateTime ? $orderDate->format('Y-m-d') : $orderDate;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getNumberOfRates(): ?int
    {
        return $this->numberOfRates;
    }

    /**
     * @param int|null $numberOfRates
     *
     * @return $this
     */
    public function setNumberOfRates(?int $numberOfRates): self
    {
        $this->numberOfRates = $numberOfRates;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDayOfPurchase(): ?string
    {
        return $this->dayOfPurchase;
    }

    /**
     * @param string|DateTime|null $dayOfPurchase
     *
     * @return $this
     */
    public function setDayOfPurchase($dayOfPurchase): self
    {
        $this->dayOfPurchase = $dayOfPurchase instanceof DateTime ? $dayOfPurchase->format('Y-m-d') : $dayOfPurchase;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getTotalPurchaseAmount(): ?float
    {
        return $this->totalPurchaseAmount;
    }

    /**
     * @param float|null $totalPurchaseAmount
     *
     * @return $this
     */
    public function setTotalPurchaseAmount(?float $totalPurchaseAmount): self
    {
        $this->totalPurchaseAmount = $totalPurchaseAmount;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getTotalInterestAmount(): ?float
    {
        return $this->totalInterestAmount;
    }

    /**
     * @param float|null $totalInterestAmount
     *
     * @return $this
     */
    public function setTotalInterestAmount(?float $totalInterestAmount): self
    {
        $this->totalInterestAmount = $totalInterestAmount;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    /**
     * @param float|null $totalAmount
     *
     * @return $this
     */
    public function setTotalAmount(?float $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getEffectiveInterestRate(): ?float
    {
        return $this->effectiveInterestRate;
    }

    /**
     * @param float|null $effectiveInterestRate
     *
     * @return $this
     */
    public function setEffectiveInterestRate(?float $effectiveInterestRate): self
    {
        $this->effectiveInterestRate = $effectiveInterestRate;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getNominalInterestRate(): ?float
    {
        return $this->nominalInterestRate;
    }

    /**
     * @param float|null $nominalInterestRate
     *
     * @return $this
     */
    public function setNominalInterestRate(?float $nominalInterestRate): self
    {
        $this->nominalInterestRate = $nominalInterestRate;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getFeeFirstRate(): ?float
    {
        return $this->feeFirstRate;
    }

    /**
     * @param float|null $feeFirstRate
     *
     * @return $this
     */
    public function setFeeFirstRate(?float $feeFirstRate): self
    {
        $this->feeFirstRate = $feeFirstRate;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getFeePerRate(): ?float
    {
        return $this->feePerRate;
    }

    /**
     * @param float|null $feePerRate
     *
     * @return $this
     */
    public function setFeePerRate(?float $feePerRate): self
    {
        $this->feePerRate = $feePerRate;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getMonthlyRate(): ?float
    {
        return $this->monthlyRate;
    }

    /**
     * @param float|null $monthlyRate
     *
     * @return $this
     */
    public function setMonthlyRate(?float $monthlyRate): self
    {
        $this->monthlyRate = $monthlyRate;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getLastRate(): ?float
    {
        return $this->lastRate;
    }

    /**
     * @param float|null $lastRate
     *
     * @return $this
     */
    public function setLastRate(?float $lastRate): self
    {
        $this->lastRate = $lastRate;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInvoiceDate(): ?string
    {
        return $this->invoiceDate;
    }

    /**
     * @param string|DateTime|null $invoiceDate
     *
     * @return InstalmentPlan
     */
    public function setInvoiceDate($invoiceDate): InstalmentPlan
    {
        $this->invoiceDate = $invoiceDate instanceof DateTime ? $invoiceDate->format('Y-m-d') : $invoiceDate;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInvoiceDueDate(): ?string
    {
        return $this->invoiceDueDate;
    }

    /**
     * @param string|DateTime|null $invoiceDueDate
     *
     * @return InstalmentPlan
     */
    public function setInvoiceDueDate($invoiceDueDate): InstalmentPlan
    {
        $this->invoiceDueDate = $invoiceDueDate instanceof DateTime ?
                $invoiceDueDate->format('Y-m-d') : $invoiceDueDate;
        return $this;
    }

    /**
     * @return stdClass[]|null
     */
    public function getInstallmentRates(): ?array
    {
        return $this->installmentRates;
    }

    /**
     * @param stdClass[] $installmentRates
     *
     * @return InstalmentPlan
     */
    protected function setInstallmentRates(array $installmentRates): InstalmentPlan
    {
        $this->installmentRates = $installmentRates;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactionParams(): array
    {
        $params = [];
        $effectiveInterestRate = $this->getEffectiveInterestRate();
        if ($effectiveInterestRate !== null) {
            $params['effectiveInterestRate'] = $effectiveInterestRate;
        }
        return $params;
    }

    /**
     * {@inheritDoc}
     */
    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        parent::handleResponse($response, $method);

        if (isset($response->installmentRates)) {
            $rates = [];
            foreach ($response->installmentRates as $rate) {
                $rates[] = $rate;
            }
            $this->setInstallmentRates($rates);
        }
    }
}
