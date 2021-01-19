<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\HirePurchase;

use heidelpayPHP\Resources\InstalmentPlan;
use Shopware\Core\Framework\Struct\Struct;
use stdClass;

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

    public function setTotalAmount(string $totalAmount): InstallmentInfo
    {
        $this->totalAmount = (float) $totalAmount;

        return $this;
    }

    public function getTotalInterest(): float
    {
        return $this->totalInterest;
    }

    public function setTotalInterest(string $totalInterest): InstallmentInfo
    {
        $this->totalInterest = (float) $totalInterest;

        return $this;
    }

    public function getNumberOfRates(): int
    {
        return $this->numberOfRates;
    }

    public function setNumberOfRates(string $numberOfRates): InstallmentInfo
    {
        $this->numberOfRates = (int) $numberOfRates;

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

    public function setTotalPurchaseAmount(string $totalPurchaseAmount): InstallmentInfo
    {
        $this->totalPurchaseAmount = (float) $totalPurchaseAmount;

        return $this;
    }

    public function getTotalInterestAmount(): float
    {
        return $this->totalInterestAmount;
    }

    public function setTotalInterestAmount(string $totalInterestAmount): InstallmentInfo
    {
        $this->totalInterestAmount = (float) $totalInterestAmount;

        return $this;
    }

    public function getEffectiveInterestRate(): float
    {
        return $this->effectiveInterestRate;
    }

    public function setEffectiveInterestRate(string $effectiveInterestRate): InstallmentInfo
    {
        $this->effectiveInterestRate = (float) $effectiveInterestRate;

        return $this;
    }

    public function getNominalInterestRate(): float
    {
        return $this->nominalInterestRate;
    }

    public function setNominalInterestRate(string $nominalInterestRate): InstallmentInfo
    {
        $this->nominalInterestRate = (float) $nominalInterestRate;

        return $this;
    }

    public function getFeeFirstRate(): float
    {
        return $this->feeFirstRate;
    }

    public function setFeeFirstRate(string $feeFirstRate): InstallmentInfo
    {
        $this->feeFirstRate = (float) $feeFirstRate;

        return $this;
    }

    public function getFeePerRate(): float
    {
        return $this->feePerRate;
    }

    public function setFeePerRate(string $feePerRate): InstallmentInfo
    {
        $this->feePerRate = (float) $feePerRate;

        return $this;
    }

    public function getMonthlyRate(): float
    {
        return $this->monthlyRate;
    }

    public function setMonthlyRate(string $monthlyRate): InstallmentInfo
    {
        $this->monthlyRate = (float) $monthlyRate;

        return $this;
    }

    public function getLastRate(): float
    {
        return $this->lastRate;
    }

    public function setLastRate(string $lastRate): InstallmentInfo
    {
        $this->lastRate = (float) $lastRate;

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

        if ($values instanceof stdClass) {
            $encoded = json_encode($values);

            if (!$encoded) {
                return $this;
            }

            $values = json_decode($encoded, true);

            if (!is_array($values) || empty($values)) {
                return $this;
            }
        }

        foreach ($values as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (property_exists(self::class, $key)) {
                $this->$method($value);
            }
        }

        return $this;
    }
}
