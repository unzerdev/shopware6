<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the Customer resource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources;

use UnzerSDK\Constants\CompanyCommercialSectorItems;
use UnzerSDK\Constants\CompanyRegistrationTypes;
use UnzerSDK\Constants\Salutations;
use UnzerSDK\Resources\EmbeddedResources\CompanyOwner;
use UnzerSDK\Unzer;
use UnzerSDK\Interfaces\ResourceServiceInterface;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\EmbeddedResources\Address;
use UnzerSDK\Resources\EmbeddedResources\CompanyInfo;
use UnzerSDK\Resources\EmbeddedResources\GeoLocation;
use UnzerSDK\Services\ResourceService;
use UnzerSDK\test\BasePaymentTest;

class CustomerTest extends BasePaymentTest
{
    //<editor-fold desc="Tests">

    /**
     * Verify setter and getter functionality.
     *
     * @test
     */
    public function settersAndGettersShouldWork(): void
    {
        $customer = new Customer();
        $this->assertNull($customer->getCustomerId());
        $this->assertNull($customer->getFirstname());
        $this->assertNull($customer->getLastname());
        $this->assertNull($customer->getBirthDate());
        $this->assertNull($customer->getPhone());
        $this->assertNull($customer->getMobile());
        $this->assertNull($customer->getEmail());
        $this->assertNull($customer->getCompany());
        $geoLocation = $customer->getGeoLocation();
        $this->assertNull($geoLocation->getClientIp());
        $this->assertNull($geoLocation->getCountryCode());

        $customer->setCustomerId('MyCustomerId-123');
        $this->assertEquals('MyCustomerId-123', $customer->getCustomerId());

        $customer->setFirstname('Peter');
        $this->assertEquals('Peter', $customer->getFirstname());

        $customer->setLastname('Universum');
        $this->assertEquals('Universum', $customer->getLastname());

        $customer->setBirthDate('1982-11-25');
        $this->assertEquals('1982-11-25', $customer->getBirthDate());

        $customer->setPhone('1234567890');
        $this->assertEquals('1234567890', $customer->getPhone());

        $customer->setMobile('01731234567');
        $this->assertEquals('01731234567', $customer->getMobile());

        $customer->setEmail('peter.universum@universum-group.de');
        $this->assertEquals('peter.universum@universum-group.de', $customer->getEmail());

        $customer->setCompany('Unzer GmbH');
        $this->assertEquals('Unzer GmbH', $customer->getCompany());
    }

    /**
     * Verify setter and getter of the billing address.
     *
     * @test
     */
    public function settersAndGettersOfBillingAddressShouldWork(): void
    {
        $address = (new Address())
            ->setState('billing_state')
            ->setCountry('billing_country')
            ->setName('billing_name')
            ->setCity('billing_city')
            ->setZip('billing_zip')
            ->setStreet('billing_street');

        $customer = new Customer();
        $billingAddress = $customer->getBillingAddress();
        $this->assertNull($billingAddress->getState());
        $this->assertNull($billingAddress->getCountry());
        $this->assertNull($billingAddress->getName());
        $this->assertNull($billingAddress->getCity());
        $this->assertNull($billingAddress->getZip());
        $this->assertNull($billingAddress->getStreet());

        $customer->setBillingAddress($address);
        $billingAddress = $customer->getBillingAddress();
        $this->assertEquals('billing_state', $billingAddress->getState());
        $this->assertEquals('billing_country', $billingAddress->getCountry());
        $this->assertEquals('billing_name', $billingAddress->getName());
        $this->assertEquals('billing_city', $billingAddress->getCity());
        $this->assertEquals('billing_zip', $billingAddress->getZip());
        $this->assertEquals('billing_street', $billingAddress->getStreet());
    }

    /**
     * Verify setter and getter of the shipping address.
     *
     * @test
     */
    public function settersAndGettersOfShippingAddressShouldWork(): void
    {
        $address = (new Address())
            ->setState('shipping_state')
            ->setCountry('shipping_country')
            ->setName('shipping_name')
            ->setCity('shipping_city')
            ->setZip('shipping_zip')
            ->setStreet('shipping_street')
            ->setShippingType('shipping_type');

        $customer = new Customer();
        $shippingAddress = $customer->getBillingAddress();
        $this->assertNull($shippingAddress->getState());
        $this->assertNull($shippingAddress->getCountry());
        $this->assertNull($shippingAddress->getName());
        $this->assertNull($shippingAddress->getCity());
        $this->assertNull($shippingAddress->getZip());
        $this->assertNull($shippingAddress->getStreet());
        $this->assertNull($shippingAddress->getShippingType());

        $customer->setShippingAddress($address);
        $shippingAddress = $customer->getShippingAddress();
        $this->assertEquals('shipping_state', $shippingAddress->getState());
        $this->assertEquals('shipping_country', $shippingAddress->getCountry());
        $this->assertEquals('shipping_name', $shippingAddress->getName());
        $this->assertEquals('shipping_city', $shippingAddress->getCity());
        $this->assertEquals('shipping_zip', $shippingAddress->getZip());
        $this->assertEquals('shipping_street', $shippingAddress->getStreet());
        $this->assertEquals('shipping_type', $shippingAddress->getShippingType());
    }

    /**
     * Verify getters and setters of CompanyInfo
     *
     * @test
     */
    public function gettersAndSettersOfCompanyInfoShouldWork(): void
    {
        $companyInfo = new CompanyInfo();
        $this->assertEquals(CompanyCommercialSectorItems::OTHER, $companyInfo->getCommercialSector());
        $this->assertNull($companyInfo->getCommercialRegisterNumber());
        $this->assertNull($companyInfo->getFunction());
        $this->assertNull($companyInfo->getRegistrationType());
        $this->assertNull($companyInfo->getCompanyType());
        $this->assertNull($companyInfo->getOwner());

        $companyInfo->setCommercialSector(CompanyCommercialSectorItems::ACCOMMODATION);
        $this->assertSame(CompanyCommercialSectorItems::ACCOMMODATION, $companyInfo->getCommercialSector());

        $companyInfo->setFunction('OWNER');
        $this->assertSame('OWNER', $companyInfo->getFunction());

        $companyInfo->setCommercialRegisterNumber('1234567890');
        $this->assertSame('1234567890', $companyInfo->getCommercialRegisterNumber());

        $companyInfo->setRegistrationType(CompanyRegistrationTypes::REGISTRATION_TYPE_REGISTERED);
        $this->assertSame(CompanyRegistrationTypes::REGISTRATION_TYPE_REGISTERED, $companyInfo->getRegistrationType());

        $companyInfo->setCompanyType('companyType');
        $this->assertSame('companyType', $companyInfo->getCompanyType());

        $owner = (new CompanyOwner())->setFirstname('firstname')
            ->setLastname('lastname')
            ->setBirthdate('01.01.1999');
        $companyInfo->setOwner($owner);
        $this->assertEquals($owner, $companyInfo->getOwner());

        $customer = new Customer();
        $this->assertNull($customer->getCompanyInfo());
        $customer->setCompanyInfo($companyInfo);
        $this->assertSame($companyInfo, $customer->getCompanyInfo());
    }

    /**
     * Verify removeRestrictedSymbols method works.
     *
     * @test
     *
     * @dataProvider removeRestrictedSymbolsMethodShouldReturnTheCorrectValueDP
     *
     * @param mixed $value
     * @param mixed $expected
     */
    public function removeRestrictedSymbolsMethodShouldReturnTheCorrectValue($value, $expected): void
    {
        $companyInfo = new CompanyInfo();
        $this->assertNull($companyInfo->getFunction());

        $companyInfo->setFunction($value);
        $this->assertEquals($expected, $companyInfo->getFunction());
    }

    /**
     * Verify salutation only uses the given values.
     *
     * @test
     */
    public function salutationShouldOnlyTakeTheAllowedValues(): void
    {
        $customer = new Customer();
        $this->assertEquals(Salutations::UNKNOWN, $customer->getSalutation());
        $customer->setSalutation(Salutations::MRS);
        $this->assertEquals(Salutations::MRS, $customer->getSalutation());
        $customer->setSalutation(Salutations::MR);
        $this->assertEquals(Salutations::MR, $customer->getSalutation());
        $customer->setSalutation('MySalutation');
        $this->assertEquals(Salutations::UNKNOWN, $customer->getSalutation());
    }

    /**
     * Verify a Customer is fetched by customerId if the id is not set.
     *
     * @test
     */
    public function customerShouldBeFetchedByCustomerIdIfIdIsNotSet(): void
    {
        $customerId = str_replace(' ', '', microtime());
        $customer = (new Customer())->setParentResource(new Unzer('s-priv-123'))->setCustomerId($customerId);
        $lastElement      = explode('/', rtrim($customer->getUri(), '/'));
        $this->assertEquals($customerId, end($lastElement));
    }

    /**
     * Verify fetchCustomerByExtCustomerId method will create a customer object set its customerId and call fetch with it.
     *
     * @test
     */
    public function fetchCustomerByOrderIdShouldCreateCustomerObjectWithCustomerIdAndCallFetch(): void
    {
        $unzer = new Unzer('s-priv-1234');
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setMethods(['fetchResource'])->setConstructorArgs([$unzer])->getMock();
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->once())->method('fetchResource')
            ->with($this->callback(static function ($customer) use ($unzer) {
                return $customer instanceof Customer &&
                    $customer->getCustomerId() === 'myCustomerId' &&
                    $customer->getUnzerObject() === $unzer;
            }));

        /** @var ResourceServiceInterface $resourceSrvMock */
        $resourceSrvMock->fetchCustomerByExtCustomerId('myCustomerId');
    }

    /**
     * Verify customer can be updated.
     *
     * @test
     */
    public function customerShouldBeUpdateable(): void
    {
        // when
        $customer = new Customer();

        // then
        $this->assertNull($customer->getCustomerId());
        $this->assertNull($customer->getFirstname());
        $this->assertNull($customer->getLastname());
        $this->assertNull($customer->getBirthDate());
        $this->assertNull($customer->getPhone());
        $this->assertNull($customer->getMobile());
        $this->assertNull($customer->getEmail());
        $this->assertNull($customer->getCompany());

        $geoLocation = $customer->getGeoLocation();
        $this->assertInstanceOf(GeoLocation::class, $geoLocation);
        $this->assertNull($geoLocation->getClientIp());
        $this->assertNull($geoLocation->getCountryCode());

        // when
        $newGeoLocation = (object)['clientIp' => 'client ip', 'countryCode' => 'country code'];
        $newValues = (object)[
            'customerId' => 'customer id',
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'birthDate' => 'birthDate',
            'phone' => 'phone',
            'mobile' => 'mobile',
            'email' => 'email',
            'company' => 'company',
            'geolocation' => $newGeoLocation
        ];
        $customer->handleResponse($newValues);

        // then
        $this->assertEquals('customer id', $customer->getCustomerId());
        $this->assertEquals('firstname', $customer->getFirstname());
        $this->assertEquals('lastname', $customer->getLastname());
        $this->assertEquals('birthDate', $customer->getBirthDate());
        $this->assertEquals('phone', $customer->getPhone());
        $this->assertEquals('mobile', $customer->getMobile());
        $this->assertEquals('email', $customer->getEmail());
        $this->assertEquals('company', $customer->getCompany());

        $geoLocation = $customer->getGeoLocation();
        $this->assertInstanceOf(GeoLocation::class, $geoLocation);
        $this->assertEquals('client ip', $geoLocation->getClientIp());
        $this->assertEquals('country code', $geoLocation->getCountryCode());
    }

    /**
     * Verify that CompanyOwner data are correctly set when handling a response.
     *
     * @test
     */
    public function handleResponseShouldSetCompanyOwnerData()
    {
        $customer = new Customer();
        $this->assertEmpty($customer->getCompanyInfo());

        $owner = (object)[
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'birthdate' => 'birthdate',
        ];

        $customerResponse = (object)[
            'companyInfo' => (object)[
                'owner' => $owner
            ]
        ];

        $customer->handleResponse($customerResponse);

        $this->assertNotEmpty($customer->getCompanyInfo());
        $companyOwner = $customer->getCompanyInfo()->getOwner();
        $this->assertNotEmpty($companyOwner);
        $this->assertEquals('firstname', $companyOwner->getFirstname());
        $this->assertEquals('lastname', $companyOwner->getLastname());
        $this->assertEquals('birthdate', $companyOwner->getBirthdate());
    }

    //</editor-fold>

    //<editor-fold desc="Data providers">

    /**
     * DataProvider for removeRestrictedSymbolsMethodShouldReturnTheCorrectValue.
     */
    public function removeRestrictedSymbolsMethodShouldReturnTheCorrectValueDP(): array
    {
        return [
            'null' => [null, null],
            'empty' => ['', ''],
            'blank' => [' ', ' '],
            'string' => ['MyTestString', 'MyTestString'],
            '<' => ['<', ''],
            '>' => ['>', ''],
            '<test>' => ['<test>', 'test'],
            'Text1' => [' >>>This >>>text >>>should <<<look <<<different ', ' This text should look different ']
        ];
    }

    //</editor-fold>
}
