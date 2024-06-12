<?php

namespace UnzerSDK\Resources\EmbeddedResources;

use UnzerSDK\Constants\CompanyCommercialSectorItems;
use UnzerSDK\Resources\AbstractUnzerResource;
use stdClass;

use function is_string;

/**
 * Company info class for B2B customer classes.
 *
 * @link  https://docs.unzer.com/
 *
 */
class CompanyInfo extends AbstractUnzerResource
{
    /** @var string $registrationType */
    protected $registrationType;

    /** @var string|null $commercialRegisterNumber */
    protected $commercialRegisterNumber;

    /** @var string|null $function */
    protected $function;

    /** @var string $commercialSector */
    protected $commercialSector = CompanyCommercialSectorItems::OTHER;

    /** @var string|null $companyType */
    protected $companyType;

    /** @var CompanyOwner|null $owner */
    protected $owner;

    /**
     * @return string|null
     */
    public function getRegistrationType(): ?string
    {
        return $this->registrationType;
    }

    /**
     * @param string|null $registrationType
     *
     * @return CompanyInfo
     */
    public function setRegistrationType(?string $registrationType): CompanyInfo
    {
        $this->registrationType = $this->removeRestrictedSymbols($registrationType);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCommercialRegisterNumber(): ?string
    {
        return $this->commercialRegisterNumber;
    }

    /**
     * @param string|null $commercialRegisterNumber
     *
     * @return CompanyInfo
     */
    public function setCommercialRegisterNumber(?string $commercialRegisterNumber): CompanyInfo
    {
        $this->commercialRegisterNumber = empty($commercialRegisterNumber) ?
            $commercialRegisterNumber : $this->removeRestrictedSymbols($commercialRegisterNumber);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFunction(): ?string
    {
        return $this->function;
    }

    /**
     * @param string|null $function
     *
     * @return CompanyInfo
     */
    public function setFunction(?string $function): CompanyInfo
    {
        $this->function = $this->removeRestrictedSymbols($function);
        return $this;
    }

    /**
     * @return string
     */
    public function getCommercialSector(): string
    {
        return $this->commercialSector;
    }

    /**
     * @param string $commercialSector
     *
     * @return CompanyInfo
     */
    public function setCommercialSector(string $commercialSector): CompanyInfo
    {
        $this->commercialSector = $this->removeRestrictedSymbols($commercialSector);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCompanyType(): ?string
    {
        return $this->companyType;
    }

    /**
     * @param string|null $companyType
     *
     * @return CompanyInfo
     */
    public function setCompanyType(?string $companyType): CompanyInfo
    {
        $this->companyType = $companyType;
        return $this;
    }

    /**
     * @return CompanyOwner|null
     */
    public function getOwner(): ?CompanyOwner
    {
        return $this->owner;
    }

    /**
     * @param CompanyOwner|null $owner
     *
     * @return CompanyInfo
     */
    public function setOwner(?CompanyOwner $owner): CompanyInfo
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * Create instances of necessary properties to handle API responses.
     *
     * @param stdClass $response
     *
     * @return void
     */
    public function instantiateObjectsFromResponse(stdClass $response): void
    {
        if (isset($response->owner) && $this->owner === null) {
            $this->owner = new CompanyOwner();
        }
    }

    /**
     * Removes some restricted symbols from the given value.
     *
     * @param string|null $value
     *
     * @return mixed
     */
    private function removeRestrictedSymbols(?string $value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return str_replace(['<', '>'], '', $value);
    }
}
