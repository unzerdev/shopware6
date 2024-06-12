<?php
/**
 * This represents the Installment Secured payment type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Resources\PaymentTypes;

use DateTime;
use UnzerSDK\Resources\InstalmentPlan;

/** @deprecated will be replaced by PaylaterInstallment.
 * @see PaylaterInstallment
 */
class InstallmentSecured extends InstalmentPlan
{
    /** @var string $iban */
    protected $iban;

    /** @var string $bic */
    protected $bic;

    /** @var string $accountHolder */
    protected $accountHolder;

    /**
     * @param InstalmentPlan|null  $selectedPlan
     * @param null|string          $iban
     * @param null|string          $accountHolder
     * @param null|DateTime|string $orderDate
     * @param null|string          $bic
     * @param null|DateTime|string $invoiceDate
     * @param null|DateTime|string $invoiceDueDate
     */
    public function __construct(InstalmentPlan $selectedPlan = null, $iban = null, $accountHolder = null, $orderDate = null, $bic = null, $invoiceDate = null, $invoiceDueDate = null)
    {
        parent::__construct();

        $this->iban = $iban;
        $this->bic = $bic;
        $this->accountHolder = $accountHolder;
        $this->setOrderDate($orderDate);
        $this->setInvoiceDate($invoiceDate);
        $this->setInvoiceDueDate($invoiceDueDate);
        $this->selectInstalmentPlan($selectedPlan);
    }

    /**
     * Updates the plan of this object with the information from the given instalment plan.
     *
     * @param InstalmentPlan|null $plan
     *
     * @return $this
     */
    public function selectInstalmentPlan(?InstalmentPlan $plan): self
    {
        if ($plan instanceof InstalmentPlan) {
            $this->handleResponse((object)$plan->expose());
        }
        return $this;
    }

    /**
     * @return string|null
     */
    public function getIban(): ?string
    {
        return $this->iban;
    }

    /**
     * @param string|null $iban
     *
     * @return $this
     */
    public function setIban(?string $iban): self
    {
        $this->iban = $iban;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBic(): ?string
    {
        return $this->bic;
    }

    /**
     * @param string|null $bic
     *
     * @return $this
     */
    public function setBic(?string $bic): self
    {
        $this->bic = $bic;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAccountHolder(): ?string
    {
        return $this->accountHolder;
    }

    /**
     * @param string|null $accountHolder
     *
     * @return $this
     */
    public function setAccountHolder(?string $accountHolder): self
    {
        $this->accountHolder = $accountHolder;
        return $this;
    }
}
