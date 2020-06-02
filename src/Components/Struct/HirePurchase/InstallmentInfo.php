<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\Struct\HirePurchase;

use heidelpayPHP\Resources\InstalmentPlan;
use Shopware\Core\Framework\Struct\Struct;

class InstallmentInfo extends Struct
{
    /** @var float */
    protected $totalAmount;

    /** @var float */
    protected $totalInterest;

    /** @var int */
    protected $numberOfRates;

    /** @var string */
    protected $dayOfPurchase;

    /** @var float */
    protected $totalPurchaseAmount;

    /** @var float */
    protected $totalInterestAmount;

    /** @var float */
    protected $effectiveInterestRate;

    /** @var float */
    protected $nominalInterestRate;

    /** @var float */
    protected $feeFirstRate;

    /** @var float */
    protected $feePerRate;

    /** @var float */
    protected $monthlyRate;

    /** @var float */
    protected $lastRate;

    /** @var string */
    protected $invoiceDate;

    /** @var string */
    protected $invoiceDueDate;

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): InstallmentInfo
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getTotalInterest(): float
    {
        return $this->totalInterest;
    }

    public function setTotalInterest(float $totalInterest): InstallmentInfo
    {
        $this->totalInterest = $totalInterest;

        return $this;
    }

    public function getNumberOfRates(): int
    {
        return $this->numberOfRates;
    }

    public function setNumberOfRates(int $numberOfRates): InstallmentInfo
    {
        $this->numberOfRates = $numberOfRates;

        return $this;
    }

    public function getDayOfPurchase(): string
    {
        return $this->dayOfPurchase;
    }

    public function setDayOfPurchase(string $dayOfPurchase): InstallmentInfo
    {
        $this->dayOfPurchase = $dayOfPurchase;

        return $this;
    }

    public function getTotalPurchaseAmount(): float
    {
        return $this->totalPurchaseAmount;
    }

    public function setTotalPurchaseAmount(float $totalPurchaseAmount): InstallmentInfo
    {
        $this->totalPurchaseAmount = $totalPurchaseAmount;

        return $this;
    }

    public function getTotalInterestAmount(): float
    {
        return $this->totalInterestAmount;
    }

    public function setTotalInterestAmount(float $totalInterestAmount): InstallmentInfo
    {
        $this->totalInterestAmount = $totalInterestAmount;

        return $this;
    }

    public function getEffectiveInterestRate(): float
    {
        return $this->effectiveInterestRate;
    }

    public function setEffectiveInterestRate(float $effectiveInterestRate): InstallmentInfo
    {
        $this->effectiveInterestRate = $effectiveInterestRate;

        return $this;
    }

    public function getNominalInterestRate(): float
    {
        return $this->nominalInterestRate;
    }

    public function setNominalInterestRate(float $nominalInterestRate): InstallmentInfo
    {
        $this->nominalInterestRate = $nominalInterestRate;

        return $this;
    }

    public function getFeeFirstRate(): float
    {
        return $this->feeFirstRate;
    }

    public function setFeeFirstRate(float $feeFirstRate): InstallmentInfo
    {
        $this->feeFirstRate = $feeFirstRate;

        return $this;
    }

    public function getFeePerRate(): float
    {
        return $this->feePerRate;
    }

    public function setFeePerRate(float $feePerRate): InstallmentInfo
    {
        $this->feePerRate = $feePerRate;

        return $this;
    }

    public function getMonthlyRate(): float
    {
        return $this->monthlyRate;
    }

    public function setMonthlyRate(float $monthlyRate): InstallmentInfo
    {
        $this->monthlyRate = $monthlyRate;

        return $this;
    }

    public function getLastRate(): float
    {
        return $this->lastRate;
    }

    public function setLastRate(float $lastRate): InstallmentInfo
    {
        $this->lastRate = $lastRate;

        return $this;
    }

    public function getInvoiceDate(): string
    {
        return $this->invoiceDate;
    }

    public function setInvoiceDate(string $invoiceDate): InstallmentInfo
    {
        $this->invoiceDate = $invoiceDate;

        return $this;
    }

    public function getInvoiceDueDate(): string
    {
        return $this->invoiceDueDate;
    }

    public function setInvoiceDueDate(string $invoiceDueDate): InstallmentInfo
    {
        $this->invoiceDueDate = $invoiceDueDate;

        return $this;
    }

    public function fromInstalmentPlan(InstalmentPlan $instalmentPlan): self
    {
        $values = $instalmentPlan->expose();

        foreach ($values as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (property_exists(self::class, $key)) {
                $this->$method($value);
            }
        }

        return $this;
    }
}
