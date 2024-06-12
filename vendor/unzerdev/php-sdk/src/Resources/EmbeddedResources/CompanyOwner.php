<?php

namespace UnzerSDK\Resources\EmbeddedResources;

use UnzerSDK\Resources\AbstractUnzerResource;

/**
 * Company owner class for B2B customer.
 *
 * @link  https://docs.unzer.com/
 *
 */
class CompanyOwner extends AbstractUnzerResource
{
    /** @var string|null $firstname */
    protected $firstname;

    /** @var string|null $lastname */
    protected $lastname;

    /** @var string|null $birthdate */
    protected $birthdate;

    /**
     * @return string|null
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * @param string|null $firstname
     *
     * @return CompanyOwner
     */
    public function setFirstname(?string $firstname): CompanyOwner
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * @param string|null $lastname
     *
     * @return CompanyOwner
     */
    public function setLastname(?string $lastname): CompanyOwner
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBirthdate(): ?string
    {
        return $this->birthdate;
    }

    /**
     * @param string|null $birthdate
     *
     * @return CompanyOwner
     */
    public function setBirthdate(?string $birthdate): CompanyOwner
    {
        $this->birthdate = $birthdate;
        return $this;
    }
}
