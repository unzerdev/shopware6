<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the AbstractUnzerResource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources;

use DateTime;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Constants\CompanyCommercialSectorItems;
use UnzerSDK\Constants\CompanyRegistrationTypes;
use UnzerSDK\Constants\Salutations;
use UnzerSDK\Constants\TransactionTypes;
use UnzerSDK\Resources\InstalmentPlans;
use UnzerSDK\Resources\PaymentTypes\Applepay;
use UnzerSDK\Unzer;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\Address;
use UnzerSDK\Resources\EmbeddedResources\CompanyInfo;
use UnzerSDK\Resources\Keypair;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Alipay;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\EPS;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Resources\PaymentTypes\Ideal;
use UnzerSDK\Resources\PaymentTypes\Invoice;
use UnzerSDK\Resources\PaymentTypes\Paypage;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebitSecured;
use UnzerSDK\Resources\Recurring;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Payout;
use UnzerSDK\Resources\TransactionTypes\Shipment;
use UnzerSDK\Resources\Webhook;
use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\test\unit\DummyResource;
use RuntimeException;
use stdClass;

class AbstractUnzerResourceTest extends BasePaymentTest
{
    /**
     * Verify setter and getter functionality.
     *
     * @test
     */
    public function settersAndGettersShouldWork(): void
    {
        $customer = new Customer();
        $this->assertNull($customer->getId());
        $this->assertNull($customer->getFetchedAt());

        $customer->setId('CustomerId-123');
        $this->assertEquals('CustomerId-123', $customer->getId());

        $customer->setFetchedAt(new DateTime('2018-12-03'));
        $this->assertEquals(new DateTime('2018-12-03'), $customer->getFetchedAt());

        $this->assertEquals(Unzer::API_VERSION, $customer->getApiVersion());
    }

    /**
     * Verify getParentResource throws exception if it is not set.
     *
     * @test
     */
    public function getParentResourceShouldThrowExceptionIfItIsNotSet(): void
    {
        $customer = new Customer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Parent resource reference is not set!');
        $customer->getParentResource();
    }

    /**
     * Verify getUnzerObject calls getParentResource.
     *
     * @test
     */
    public function getUnzerObjectShouldCallGetParentResourceOnce(): void
    {
        $customerMock = $this->getMockBuilder(Customer::class)->setMethods(['getParentResource'])->getMock();
        $customerMock->expects($this->once())->method('getParentResource');

        /** @var Customer $customerMock */
        $customerMock->getUnzerObject();
    }

    /**
     * Verify getter/setter of ParentResource and Unzer object.
     *
     * @test
     */
    public function parentResourceAndUnzerGetterSetterShouldWork(): void
    {
        $unzerObj = new Unzer('s-priv-123');
        $customer     = new Customer();
        $customer->setParentResource($unzerObj);
        $this->assertSame($unzerObj, $customer->getParentResource());
        $this->assertSame($unzerObj, $customer->getUnzerObject());
    }

    /**
     * Verify getUri will call parentResource.
     *
     * @test
     */
    public function getUriWillCallGetUriOnItsParentResource(): void
    {
        $unzerMock = $this->getMockBuilder(Unzer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUri'])
            ->getMock();
        $unzerMock->expects($this->once())->method('getUri')->willReturn('parent/resource/path/');

        /** @var Customer $unzerMock */
        $customer = (new Customer())->setParentResource($unzerMock);
        $this->assertEquals('parent/resource/path/customers', $customer->getUri());
    }

    /**
     * Verify getUri will return the expected path with id if the flag is set.
     *
     * @test
     *
     * @dataProvider uriDataProvider
     *
     * @param AbstractUnzerResource $resource
     * @param string                $resourcePath
     */
    public function getUriWillAddIdToTheUriIfItIsSetAndAppendIdIsSet(AbstractUnzerResource$resource, $resourcePath): void
    {
        $unzerMock = $this->getMockBuilder(Unzer::class)->disableOriginalConstructor()->setMethods(['getUri'])->getMock();
        $unzerMock->method('getUri')->willReturn('parent/resource/path/');

        /** @var Unzer $unzerMock */
        $resource->setParentResource($unzerMock)->setId('myId');
        $this->assertEquals($resourcePath . '/myId', $resource->getUri(true, HttpAdapterInterface::REQUEST_POST));
        $this->assertEquals($resourcePath, $resource->getUri(false, HttpAdapterInterface::REQUEST_POST));
    }

    /**
     * Verify payment types use the path without payment resource name in uri for get request. Other resources should
     * not be affected by that.
     *
     * @dataProvider fetchUriDataProvider
     *
     * @test
     *
     * @param mixed $resourcePath
     */
    public function fetchPaymentContainsNoTypeNameInUri(AbstractUnzerResource $resource, $resourcePath): void
    {
        $unzerMock = $this->getMockBuilder(Unzer::class)->disableOriginalConstructor()->setMethods(['getUri'])->getMock();
        $unzerMock->method('getUri')->willReturn('parent/resource/path/');
        $resource->setParentResource($unzerMock)
            ->setId('myId');
        $this->assertEquals($resourcePath . '/myId', $resource->getUri(true, HttpAdapterInterface::REQUEST_GET));
        $this->assertEquals($resourcePath, $resource->getUri(false, HttpAdapterInterface::REQUEST_GET));
    }

    /**
     * Verify that installment plans use the correct path for fetching. Special case, fetching Instalmentplans contains
     * Installment-secured as parent resource that should appear in resource path.
     *
     * @test
     */
    public function fetchInstalmentPlansShouldUseUriWithTypeName()
    {
        $unzerMock = $this->getMockBuilder(Unzer::class)->disableOriginalConstructor()->setMethods(['getUri'])->getMock();
        $unzerMock->method('getUri')->willReturn('parent/resource/path/');

        $installmentSecured = (new InstallmentSecured())->setParentResource($unzerMock);
        $instalmentPlans = (new InstalmentPlans(119.0, 'EUR', 4.99))
            ->setParentResource($installmentSecured);

        $this->assertEquals(
            'parent/resource/path/types/installment-secured/plans?amount=119&currency=EUR&effectiveInterest=4.99',
            $instalmentPlans->getUri(false, HttpAdapterInterface::REQUEST_GET)
        );
    }

    /**
     * Verify getUri with appendId == true will append the externalId if it is returned and the id is not set.
     *
     * @test
     */
    public function getUriWillAddExternalIdToTheUriIfTheIdIsNotSetButAppendIdIs(): void
    {
        $unzerMock = $this->getMockBuilder(Unzer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUri'])
            ->getMock();
        $unzerMock->method('getUri')->willReturn('parent/resource/path/');

        $customerMock = $this->getMockBuilder(Customer::class)->setMethods(['getExternalId'])->getMock();
        $customerMock->expects($this->atLeast(1))->method('getExternalId')->willReturn('myExternalId');

        /** @var Customer $customerMock */
        /** @var Unzer $unzerMock */
        $customerMock->setParentResource($unzerMock);
        $this->assertEquals('parent/resource/path/customers/myExternalId', $customerMock->getUri());
        $this->assertEquals('parent/resource/path/customers', $customerMock->getUri(false));
    }

    /**
     * Verify updateValues will update child objects.
     *
     * @test
     */
    public function updateValuesShouldUpdateChildObjects(): void
    {
        $address = (new Address())
            ->setState('DE-BW')
            ->setCountry('DE')
            ->setName('Max Mustermann')
            ->setCity('Heidelberg')
            ->setZip('69115')
            ->setStreet('Musterstrasse 15');

        $info = (new CompanyInfo())
            ->setRegistrationType(CompanyRegistrationTypes::REGISTRATION_TYPE_NOT_REGISTERED)
            ->setCommercialRegisterNumber('0987654321')
            ->setFunction('CEO')
            ->setCommercialSector(CompanyCommercialSectorItems::AIR_TRANSPORT);

        $testResponse                 = new stdClass();
        $testResponse->billingAddress = json_decode($address->jsonSerialize(), false);
        $testResponse->companyInfo    = json_decode($info->jsonSerialize(), false);

        $customer = new Customer();
        $customer->handleResponse($testResponse);

        $billingAddress = $customer->getBillingAddress();
        $this->assertEquals('DE-BW', $billingAddress->getState());
        $this->assertEquals('DE', $billingAddress->getCountry());
        $this->assertEquals('Max Mustermann', $billingAddress->getName());
        $this->assertEquals('Heidelberg', $billingAddress->getCity());
        $this->assertEquals('69115', $billingAddress->getZip());
        $this->assertEquals('Musterstrasse 15', $billingAddress->getStreet());

        $companyInfo = $customer->getCompanyInfo();
        $this->assertEquals(CompanyRegistrationTypes::REGISTRATION_TYPE_NOT_REGISTERED, $companyInfo->getRegistrationType());
        $this->assertEquals('0987654321', $companyInfo->getCommercialRegisterNumber());
        $this->assertEquals('CEO', $companyInfo->getFunction());
        $this->assertEquals(CompanyCommercialSectorItems::AIR_TRANSPORT, $companyInfo->getCommercialSector());
    }

    /**
     * Verify updateValues will update resource fields with values from processing group in response.
     *
     * @test
     */
    public function updateValuesShouldUpdateValuesFromProcessingInTheActualObject(): void
    {
        $testResponse  = new stdClass();
        $testResponse->processing = (object)['customerId' => 'cst-id', 'firstname' => 'first', 'lastname' => 'last'];

        $customer = CustomerFactory::createCustomer('firstName', 'lastName')->setCustomerId('customerId');
        $this->assertEquals('customerId', $customer->getCustomerId());
        $this->assertEquals('firstName', $customer->getFirstname());
        $this->assertEquals('lastName', $customer->getLastname());

        $customer->handleResponse($testResponse);
        $this->assertEquals('cst-id', $customer->getCustomerId());
        $this->assertEquals('first', $customer->getFirstname());
        $this->assertEquals('last', $customer->getLastname());
    }

    /**
     * Verify json_serialize translates a resource in valid json format and values are exposed correctly.
     *
     * @test
     */
    public function jsonSerializeShouldTranslateResourceIntoJson(): void
    {
        $unzer = new Unzer('s-priv-123');
        $address   = (new Address())
            ->setName('Peter Universum')
            ->setStreet('Hugo-Junkers-Str. 5')
            ->setZip('60386')
            ->setCity('Frankfurt am Main')
            ->setCountry('DE')
            ->setState('DE-BO');

        $customer = (new Customer())
            ->setCustomerId('CustomerId')
            ->setFirstname('Peter')
            ->setLastname('Universum')
            ->setSalutation(Salutations::MR)
            ->setCompany('unzer GmbH')
            ->setBirthDate('1989-12-24')
            ->setEmail('peter.universum@universum-group.de')
            ->setMobile('+49172123456')
            ->setPhone('+4962216471100')
            ->setBillingAddress($address)
            ->setShippingAddress($address)
            ->setParentResource($unzer);

        $customer->setSpecialParams(['param1' => 'value1', 'param2' => 'value2']);

        $expectedJson = '{"billingAddress":{"city":"Frankfurt am Main","country":"DE","name":"Peter Universum",' .
            '"state":"DE-BO","street":"Hugo-Junkers-Str. 5","zip":"60386"},"birthDate":"1989-12-24",' .
            '"company":"unzer GmbH","customerId":"CustomerId","email":"peter.universum@universum-group.de",' .
            '"firstname":"Peter","lastname":"Universum","mobile":"+49172123456","param1":"value1","param2":"value2",' .
            '"phone":"+4962216471100","salutation":"mr","shippingAddress":{"city":"Frankfurt am Main","country":"DE",' .
            '"name":"Peter Universum","state":"DE-BO","street":"Hugo-Junkers-Str. 5","zip":"60386"}}';
        $this->assertEquals($expectedJson, $customer->jsonSerialize());
    }

    /**
     * Verify that empty values are not set on expose.
     *
     * @test
     */
    public function nullValuesShouldBeUnsetOnExpose(): void
    {
        $customer = new Customer();
        $customer->setEmail('my.email@test.com');
        $this->assertArrayHasKey('email', $customer->expose());

        $customer->setEmail(null);
        $this->assertArrayNotHasKey('email', $customer->expose());
    }

    /**
     * Verify that ids of linked resources are added.
     *
     * @test
     */
    public function idsOfLinkedResourcesShouldBeAddedOnExpose(): void
    {
        $customer = CustomerFactory::createCustomer('Max', ' Mustermann');
        $customer->setId('MyTestId');
        $dummy      = new DummyUnzerResource($customer);
        $dummyArray = $dummy->expose();
        $this->assertArrayHasKey('resources', $dummyArray);
        $this->assertArrayHasKey('customerId', $dummyArray['resources']);
        $this->assertEquals('MyTestId', $dummyArray['resources']['customerId']);
    }

    /**
     * Verify null is returned as externalId if the class does not implement the getter any.
     *
     * @test
     */
    public function getExternalIdShouldReturnNullIfItIsNotImplementedInTheExtendingClass(): void
    {
        $customer = CustomerFactory::createCustomer('Max', ' Mustermann');
        $customer->setId('MyTestId');
        $dummy = new DummyUnzerResource($customer);
        $this->assertNull($dummy->getExternalId());
    }

    /**
     * Verify float values are rounded to 4 decimal places on expose.
     * The object and the transmitted value will be updated.
     *
     * @test
     */
    public function moreThenFourDecimalPlaces(): void
    {
        // general
        $object = new DummyResource();
        $object->setTestFloat(1.23456789);
        $this->assertEquals(1.23456789, $object->getTestFloat());

        $reduced = $object->expose();
        $this->assertEquals(['testFloat' => 1.2346], $reduced);
        $this->assertEquals(1.2346, $object->getTestFloat());

        // additionalAttributes
        $ppg = new Paypage(1.23456789, 'EUR', self::RETURN_URL);
        $ppg->setEffectiveInterestRate(12.3456789);
        $this->assertEquals(12.3457, $ppg->expose()['additionalAttributes']['effectiveInterestRate']);
        $this->assertEquals(12.3457, $ppg->getEffectiveInterestRate());
    }

    /**
     * Verify additionalAttributes are set/get properly.
     *
     * @test
     */
    public function additionalAttributesShouldBeSettable(): void
    {
        $paypage = new Paypage(123.4, 'EUR', self::RETURN_URL);

        // when
        $paypage->setEffectiveInterestRate(123.4567);

        // then
        $this->assertEquals(123.4567, $paypage->getEffectiveInterestRate());
        $this->assertEquals(123.4567, $paypage->expose()['additionalAttributes']['effectiveInterestRate']);

        // when
        $paypage->handleResponse((object)['additionalAttributes' => ['effectiveInterestRate' => 1234.567]]);

        // then
        $this->assertEquals(1234.567, $paypage->getEffectiveInterestRate());
        $this->assertEquals(1234.567, $paypage->expose()['additionalAttributes']['effectiveInterestRate']);
    }

    //<editor-fold desc="Data Providers">

    /**
     * Data provider for getUriWillAddIdToTheUriIfItIsSetAndAppendIdIsSet.
     *
     * @return array
     */
    public function uriDataProvider(): array
    {
        return [
            'Customer' => [new Customer(), 'parent/resource/path/customers'],
            'Keypair' => [new Keypair(), 'parent/resource/path/keypair'],
            'Payment' => [new Payment(), 'parent/resource/path/payments'],
            'Card' => [new Card('', '03/30'), 'parent/resource/path/types/card'],
            'Ideal' => [new Ideal(), 'parent/resource/path/types/ideal'],
            'EPS' => [new EPS(), 'parent/resource/path/types/eps'],
            'Alipay' => [new Alipay(), 'parent/resource/path/types/alipay'],
            'ApplePay' => [new Applepay('EC_v1', 'data', 'sig', null), 'parent/resource/path/types/applepay'],
            'SepaDirectDebit' => [new SepaDirectDebit(''), 'parent/resource/path/types/sepa-direct-debit'],
            'SepaDirectDebitSecured' => [new SepaDirectDebitSecured(''), 'parent/resource/path/types/sepa-direct-debit-secured'],
            'Invoice' => [new Invoice(), 'parent/resource/path/types/invoice'],
            'Cancellation' => [new Cancellation(), 'parent/resource/path/cancels'],
            'Authorization' => [new Authorization(), 'parent/resource/path/authorize'],
            'Shipment' => [new Shipment(), 'parent/resource/path/shipments'],
            'Charge' => [new Charge(), 'parent/resource/path/charges'],
            'Metadata' => [new Metadata(), 'parent/resource/path/metadata'],
            'Basket' => [new Basket(), 'parent/resource/path/baskets'],
            'Webhook' => [new Webhook(), 'parent/resource/path/webhooks'],
            'Webhooks' => [new Webhook(), 'parent/resource/path/webhooks'],
            'Recurring' => [new Recurring('s-crd-123', ''), 'parent/resource/path/types/s-crd-123/recurring'],
            'Payout' => [new Payout(), 'parent/resource/path/payouts'],
            'PayPage charge' => [new Paypage(123.4567, 'EUR', 'url'), 'parent/resource/path/paypage/charge'],
            'PayPage authorize' => [(new Paypage(123.4567, 'EUR', 'url'))->setAction(TransactionTypes::AUTHORIZATION), 'parent/resource/path/paypage/authorize'],
            'InstallmentSecured' => [new InstallmentSecured(), 'parent/resource/path/types/installment-secured']
        ];
    }

    //</editor-fold>
    public function fetchUriDataProvider()
    {
        return [
            // Payment types.
            'Alipay' => [new Alipay(), 'parent/resource/path/types'],
            'ApplePay' => [new Applepay('EC_v1', 'data', 'sig', null), 'parent/resource/path/types'],
            'Card' => [new Card('', '03/30'), 'parent/resource/path/types'],
            'EPS' => [new EPS(), 'parent/resource/path/types'],
            'Ideal' => [new Ideal(), 'parent/resource/path/types'],
            'InstallmentSecured' => [new InstallmentSecured(), 'parent/resource/path/types'],
            'Invoice' => [new Invoice(), 'parent/resource/path/types'],
            'SepaDirectDebit' => [new SepaDirectDebit(''), 'parent/resource/path/types'],
            'SepaDirectDebitSecured' => [new SepaDirectDebitSecured(''), 'parent/resource/path/types'],

            // Other resources Uris should behave as before.
            'Customer' => [new Customer(), 'parent/resource/path/customers'],
            'Keypair' => [new Keypair(), 'parent/resource/path/keypair'],
            'Payment' => [new Payment(), 'parent/resource/path/payments'],
            'Cancellation' => [new Cancellation(), 'parent/resource/path/cancels'],
            'Authorization' => [new Authorization(), 'parent/resource/path/authorize'],
            'Shipment' => [new Shipment(), 'parent/resource/path/shipments'],
            'Charge' => [new Charge(), 'parent/resource/path/charges'],
            'Metadata' => [new Metadata(), 'parent/resource/path/metadata'],
            'Basket' => [new Basket(), 'parent/resource/path/baskets'],
            'Webhook' => [new Webhook(), 'parent/resource/path/webhooks'],
            'Webhooks' => [new Webhook(), 'parent/resource/path/webhooks'],
            'Recurring' => [new Recurring('s-crd-123', ''), 'parent/resource/path/types/s-crd-123/recurring'],
            'Payout' => [new Payout(), 'parent/resource/path/payouts'],
            'PayPage charge' => [new Paypage(123.4567, 'EUR', 'url'), 'parent/resource/path/paypage'],
            'PayPage authorize' => [(new Paypage(123.4567, 'EUR', 'url'))->setAction(TransactionTypes::AUTHORIZATION), 'parent/resource/path/paypage'],
        ];
    }
}
