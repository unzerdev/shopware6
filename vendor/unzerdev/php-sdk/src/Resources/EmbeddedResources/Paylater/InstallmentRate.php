<?php

namespace UnzerSDK\Resources\EmbeddedResources\Paylater;

/**
 * Installment rate class for PaylaterInstallment payment types.
 *
 * @link  https://docs.unzer.com/
 *
 */
class InstallmentRate
{
    /** @var string|null $date */
    protected $date;

    /** @var string|null $rate Amount of rate.*/
    protected $rate;

    /**
     * @param string|null $date
     * @param string|null $rate
     */
    public function __construct(?string $date, ?string $rate)
    {
        $this->date = $date;
        $this->rate = $rate;
    }

    /**
     * @return string|null
     */
    public function getDate(): ?string
    {
        return $this->date;
    }

    /**
     * @param string|null $date
     *
     * @return InstallmentRate
     */
    public function setDate(?string $date): InstallmentRate
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRate(): ?string
    {
        return $this->rate;
    }

    /**
     * @param string|null $rate
     *
     * @return InstallmentRate
     */
    public function setRate(?string $rate): InstallmentRate
    {
        $this->rate = $rate;
        return $this;
    }
}
