<?php

namespace UnzerSDK\Resources;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Constants\Salutations;
use UnzerSDK\Resources\EmbeddedResources\Address;
use UnzerSDK\Resources\EmbeddedResources\CompanyInfo;
use UnzerSDK\Traits\HasGeoLocation;
use stdClass;

use function in_array;

/**
 * This represents the customer resource.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Customer extends AbstractUnzerResource
{
    use HasGeoLocation;

    /** @var string $firstname */
    protected $firstname;

    /** @var string $lastname */
    protected $lastname;

    /** @var string $salutation */
    protected $salutation = Salutations::UNKNOWN;

    /** @var string $birthDate */
    protected $birthDate;

    /** @var string $company*/
    protected $company;

    /** @var string $email*/
    protected $email;

    /** @var string $phone*/
    protected $phone;

    /** @var string $mobile*/
    protected $mobile;

    /** @var Address $billingAddress */
    protected $billingAddress;

    /** @var Address $shippingAddress */
    protected $shippingAddress;

    /** @var string $customerId */
    protected $customerId;

    /** @var CompanyInfo $companyInfo */
    protected $companyInfo;

    /** @var string $language */
    protected $language;

    /**
     * Customer constructor.
     */
    public function __construct()
    {
        $this->billingAddress = new Address();
        $this->shippingAddress = new Address();
    }

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
     * @return Customer
     */
    public function setFirstname(?string $firstname): Customer
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
     * @return Customer
     */
    public function setLastname(?string $lastname): Customer
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * @return string
     */
    public function getSalutation(): string
    {
        return $this->salutation;
    }

    /**
     * @param string|null $salutation
     *
     * @return Customer
     */
    public function setSalutation(?string $salutation): Customer
    {
        $allowedSalutations = [Salutations::MR, Salutations::MRS, Salutations::UNKNOWN];
        $this->salutation = in_array($salutation, $allowedSalutations, true) ? $salutation : Salutations::UNKNOWN;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    /**
     * @param string|null $birthday
     *
     * @return Customer
     */
    public function setBirthDate(?string $birthday): Customer
    {
        $this->birthDate = $birthday;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @param string|null $company
     *
     * @return Customer
     */
    public function setCompany(?string $company): Customer
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     *
     * @return Customer
     */
    public function setEmail(?string $email): Customer
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     *
     * @return Customer
     */
    public function setPhone(?string $phone): Customer
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    /**
     * @param string|null $mobile
     *
     * @return Customer
     */
    public function setMobile(?string $mobile): Customer
    {
        $this->mobile = $mobile;
        return $this;
    }

    /**
     * @return Address
     */
    public function getBillingAddress(): Address
    {
        return $this->billingAddress;
    }

    /**
     * @param Address $billingAddress
     *
     * @return Customer
     */
    public function setBillingAddress(Address $billingAddress): Customer
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    /**
     * @return Address
     */
    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }

    /**
     * @param Address $shippingAddress
     *
     * @return Customer
     */
    public function setShippingAddress(Address $shippingAddress): Customer
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @param string|null $customerId
     *
     * @return Customer
     */
    public function setCustomerId(?string $customerId): Customer
    {
        $this->customerId = $customerId;
        return $this;
    }

    /**
     * @return CompanyInfo|null
     */
    public function getCompanyInfo(): ?CompanyInfo
    {
        return $this->companyInfo;
    }

    /**
     * @param CompanyInfo|null $companyInfo
     *
     * @return Customer
     */
    public function setCompanyInfo(?CompanyInfo $companyInfo): Customer
    {
        $this->companyInfo = $companyInfo;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string|null $language
     *
     * @return Customer
     */
    public function setLanguage(?string $language): Customer
    {
        $this->language = $language;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'customers';
    }

    /**
     * {@inheritDoc}
     */
    public function getExternalId(): ?string
    {
        return $this->getCustomerId();
    }

    /**
     * {@inheritDoc}
     */
    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        if (isset($response->companyInfo) && $this->companyInfo === null) {
            $this->companyInfo = new CompanyInfo();
            $this->companyInfo->instantiateObjectsFromResponse($response->companyInfo);
        }

        parent::handleResponse($response, $method);
    }
}
