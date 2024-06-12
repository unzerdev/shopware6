<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and
 * functionality of the Customer resource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Constants\Salutations;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\test\BaseIntegrationTest;

use function microtime;

class CustomerTest extends BaseIntegrationTest
{
    //<editor-fold desc="General Customer">

    /**
     * Min customer should be creatable via the sdk.
     *
     * @test
     *
     * @return Customer
     */
    public function minCustomerCanBeCreatedAndFetched(): Customer
    {
        $customer = $this->getMinimalCustomer();
        $this->assertEmpty($customer->getId());
        $this->unzer->createCustomer($customer);
        $this->assertNotEmpty($customer->getId());

        $geoLocation = $customer->getGeoLocation();
        $this->assertNull($geoLocation->getClientIp());
        $this->assertNull($geoLocation->getCountryCode());

        $fetchedCustomer = $this->unzer->fetchCustomer($customer->getId());
        $exposeArray     = $customer->expose();
        $exposeArray['salutation'] = Salutations::UNKNOWN;
        $this->assertEquals($exposeArray, $fetchedCustomer->expose());

        $geoLocation = $fetchedCustomer->getGeoLocation();
        $this->assertNotEmpty($geoLocation->getClientIp());
        $this->assertNotEmpty($geoLocation->getCountryCode());

        return $customer;
    }

    /**
     * Max customer should be creatable via the sdk.
     *
     * @test
     *
     * @return Customer
     */
    public function maxCustomerCanBeCreatedAndFetched(): Customer
    {
        $customer = $this->getMaximumCustomer();
        $this->assertEmpty($customer->getId());
        $this->unzer->createCustomer($customer);
        $this->assertNotEmpty($customer->getId());

        $fetchedCustomer = $this->unzer->fetchCustomer($customer->getId());
        $this->assertEquals($customer->expose(), $fetchedCustomer->expose());

        return $customer;
    }

    /**
     * Verify shipping type can be set for shipping address of customer resource.
     *
     * @test
     */
    public function customerWithShippingTypeCanBeCreatedAndFetched()
    {
        $customer   = $this->getMaximumCustomerInclShippingAddress();
        $customer->getShippingAddress()->setShippingType('shippingType');

        $this->unzer->createCustomer($customer);
        $fetchedCustomer = $this->unzer->fetchCustomer($customer->getId());
        $this->assertEquals('shippingType', $fetchedCustomer->getShippingAddress()->getShippingType());
    }

    /**
     * @param Customer $customer
     *
     * @depends maxCustomerCanBeCreatedAndFetched
     *
     * @test
     */
    public function customerCanBeFetchedById(Customer $customer): void
    {
        $fetchedCustomer = $this->unzer->fetchCustomer($customer->getId());
        $this->assertEquals($customer->getId(), $fetchedCustomer->getId());
    }

    /**
     * @depends maxCustomerCanBeCreatedAndFetched
     *
     * @test
     */
    public function customerCanBeFetchedByCustomerId(): void
    {
        $customerId = 'c' . self::generateRandomId();
        $customer = $this->getMaximumCustomer()->setCustomerId($customerId);
        $this->unzer->createCustomer($customer);

        $fetchedCustomer = $this->unzer->fetchCustomerByExtCustomerId($customer->getCustomerId());
        $this->assertEquals($customer->expose(), $fetchedCustomer->expose());
    }

    /**
     * @param Customer $customer
     *
     * @depends maxCustomerCanBeCreatedAndFetched
     *
     * @test
     */
    public function customerCanBeFetchedByObject(Customer $customer): void
    {
        $customerToFetch = (new Customer())->setId($customer->getId());
        $fetchedCustomer = $this->unzer->fetchCustomer($customerToFetch);
        $this->assertEquals($customer->getId(), $fetchedCustomer->getId());
    }

    /**
     * @param Customer $customer
     *
     * @depends maxCustomerCanBeCreatedAndFetched
     *
     * @test
     */
    public function customerCanBeFetchedByObjectWithData(Customer $customer): void
    {
        $customerToFetch = $this->getMinimalCustomer()->setId($customer->getId());
        $this->assertNotEquals($customer->getFirstname(), $customerToFetch->getFirstname());

        $fetchedCustomer = $this->unzer->fetchCustomer($customerToFetch);
        $this->assertEquals($customer->getFirstname(), $fetchedCustomer->getFirstname());
    }

    /**
     * Customer can be referenced by payment.
     *
     * @test
     */
    public function transactionShouldCreateAndReferenceCustomerIfItDoesNotExistYet(): void
    {
        $customerId = 'c' . self::generateRandomId();
        $customer   = $this->getMaximumCustomerInclShippingAddress()->setCustomerId($customerId);

        /** @var Paypal $paypal */
        $paypal = $this->unzer->createPaymentType(new Paypal());
        $authorization = $paypal->authorize(12.0, 'EUR', self::RETURN_URL, $customer);

        $secPayment = $this->unzer->fetchPayment($authorization->getPayment()->getId());

        /** @var Customer $secCustomer */
        $secCustomer = $secPayment->getCustomer();
        $this->assertNotNull($secCustomer);
        $this->assertEquals($customer->expose(), $secCustomer->expose());
    }

    /**
     * Customer can be referenced by payment.
     *
     * @test
     */
    public function transactionShouldReferenceCustomerIfItExist(): void
    {
        $customer = $this->getMaximumCustomer();
        $this->unzer->createCustomer($customer);

        /** @var Paypal $paypal */
        $paypal = $this->unzer->createPaymentType(new Paypal());
        $authorization = $paypal->authorize(12.0, 'EUR', self::RETURN_URL, $customer);

        $secPayment = $this->unzer->fetchPayment($authorization->getPayment()->getId());

        /** @var Customer $secCustomer */
        $secCustomer = $secPayment->getCustomer();
        $this->assertNotNull($secCustomer);
        $this->assertEquals($customer->expose(), $secCustomer->expose());
    }

    /**
     * Customer can be referenced by payment.
     *
     * @test
     */
    public function transactionShouldReferenceCustomerIfItExistAndItsIdHasBeenPassed(): void
    {
        $customer = $this->getMaximumCustomer();
        $this->unzer->createCustomer($customer);

        /** @var Paypal $paypal */
        $paypal = $this->unzer->createPaymentType(new Paypal());
        $authorization = $paypal->authorize(12.0, 'EUR', self::RETURN_URL, $customer->getId());

        $secPayment = $this->unzer->fetchPayment($authorization->getPayment()->getId());

        /** @var Customer $secCustomer */
        $secCustomer = $secPayment->getCustomer();
        $this->assertNotNull($secCustomer);
        $this->assertEquals($customer->expose(), $secCustomer->expose());
    }

    /**
     * Customer can be updated.
     *
     * @depends maxCustomerCanBeCreatedAndFetched
     *
     * @test
     *
     * @param Customer $customer
     */
    public function customerShouldBeUpdateable(Customer $customer): void
    {
        $this->assertEquals('Peter', $customer->getFirstname());
        $customer->setFirstname('Not Peter');
        $this->unzer->updateCustomer($customer);
        $this->assertEquals('Not Peter', $customer->getFirstname());

        $fetchedCustomer = $this->unzer->fetchCustomer($customer->getId());
        $this->assertEquals($customer->getId(), $fetchedCustomer->getId());
        $this->assertEquals('Not Peter', $fetchedCustomer->getFirstname());
    }

    /**
     * Customer can be deleted.
     *
     * @depends maxCustomerCanBeCreatedAndFetched
     *
     * @test
     *
     * @param Customer $customer
     */
    public function customerShouldBeDeletableById(Customer $customer): void
    {
        $this->assertNotNull($customer);
        $this->assertNotNull($customer->getId());

        $this->unzer->deleteCustomer($customer->getId());

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_CUSTOMER_DOES_NOT_EXIST);
        $this->unzer->fetchCustomer($customer->getId());
    }

    /**
     * Customer can be deleted.
     *
     * @test
     */
    public function customerShouldBeDeletableByObject(): void
    {
        $customer = $this->unzer->createCustomer($this->getMaximumCustomer());

        $fetchedCustomer = $this->unzer->fetchCustomer($customer->getId());
        $this->assertNotNull($customer);
        $this->assertNotNull($customer->getId());

        $this->unzer->deleteCustomer($customer);

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_CUSTOMER_DOES_NOT_EXIST);
        $this->unzer->fetchCustomer($fetchedCustomer->getId());
    }

    /**
     * Verify an Exception is thrown if the customerId already exists.
     *
     * @test
     */
    public function apiShouldReturnErrorIfCustomerAlreadyExists(): void
    {
        $customerId = str_replace(' ', '', microtime());

        // create customer with api
        $customer = $this->unzer->createCustomer($this->getMaximumCustomer()->setCustomerId($customerId));
        $this->assertNotEmpty($customer->getCustomerId());

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_CUSTOMER_ID_ALREADY_EXISTS);

        // create new customer with the same customerId
        $this->unzer->createCustomer($this->getMaximumCustomer()->setCustomerId($customerId));
    }

    /**
     * Verify a Customer is fetched and updated when its customerId already exist.
     *
     * @test
     */
    public function customerShouldBeFetchedByCustomerIdAndUpdatedIfItAlreadyExists(): void
    {
        $customerId = str_replace(' ', '', microtime());

        try {
            // fetch non-existing customer by customerId
            $this->unzer->fetchCustomerByExtCustomerId($customerId);
            $this->assertTrue(false, 'Exception should be thrown here.');
        } catch (UnzerApiException $e) {
            $this->assertEquals(ApiResponseCodes::API_ERROR_CUSTOMER_CAN_NOT_BE_FOUND, $e->getCode());
            $this->assertNotNull($e->getErrorId());
        }

        // create customer with api
        $customer = $this->unzer->createOrUpdateCustomer($this->getMaximumCustomer()->setCustomerId($customerId));
        $this->assertNotEmpty($customer->getCustomerId());
        $this->assertEquals($customerId, $customer->getCustomerId());
        $this->assertEquals('Peter', $customer->getFirstname());

        $newCustomerData = $this->getMaximumCustomer()->setCustomerId($customerId)->setFirstname('Petra');
        $this->unzer->createOrUpdateCustomer($newCustomerData);

        $this->assertEquals('Petra', $newCustomerData->getFirstname());
        $this->assertEquals($customerId, $newCustomerData->getCustomerId());
        $this->assertEquals($customer->getId(), $newCustomerData->getId());
    }

    /**
     * Verify customer address can take a name as long as both first and lastname concatenated.
     *
     * @test
     */
    public function addressNameCanHoldFirstAndLastNameConcatenated(): void
    {
        $customerId = 'c' . self::generateRandomId();
        $customer   = $this->getMaximumCustomerInclShippingAddress()->setCustomerId($customerId);
        $longName   = 'firstfirstfirstfirstfirstfirstfirstfirst lastlastlastlastlastlastlastlastlastlast';
        $customer->getShippingAddress()->setName($longName);
        $this->unzer->createCustomer($customer);
        $this->assertEquals($longName, $customer->getShippingAddress()->getName());

        $veryLongName   = $longName . 'X';
        $customer->getShippingAddress()->setName($veryLongName);
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_ADDRESS_NAME_TO_LONG);
        $this->unzer->updateCustomer($customer);
    }

    //</editor-fold>

    //<editor-fold desc="not registered B2B Customer">

    /**
     * Not registered B2B customer should be creatable.
     *
     * @test
     *
     * @return Customer
     */
    public function minNotRegisteredB2bCustomerCanBeCreatedAndFetched(): Customer
    {
        $customer = $this->getMinimalNotRegisteredB2bCustomer();
        $this->assertEmpty($customer->getId());
        $this->unzer->createCustomer($customer);
        $this->assertNotEmpty($customer->getId());

        $fetchedCustomer = $this->unzer->fetchCustomer($customer->getId());
        $exposeArray     = $customer->expose();
        $exposeArray['salutation'] = Salutations::UNKNOWN;
        $this->assertEquals($exposeArray, $fetchedCustomer->expose());

        return $customer;
    }

    /**
     * Max not registered customer should be creatable.
     *
     * @test
     */
    public function maxNotRegisteredB2bCustomerCanBeCreatedAndFetched(): void
    {
        $customer = $this->getMaximalNotRegisteredB2bCustomer();
        $this->assertEmpty($customer->getId());
        $this->unzer->createCustomer($customer);
        $this->assertNotEmpty($customer->getId());

        $fetchedCustomer = $this->unzer->fetchCustomer($customer->getId());
        $this->assertEquals($customer->expose(), $fetchedCustomer->expose());
    }

    //</editor-fold>

    //<editor-fold desc="registered B2B Customer">

    /**
     * Registered B2B customer should be creatable.
     *
     * @test
     *
     * @return Customer
     */
    public function minRegisteredB2bCustomerCanBeCreatedAndFetched(): Customer
    {
        $customer = $this->getMinimalRegisteredB2bCustomer();
        $this->assertEmpty($customer->getId());
        $this->unzer->createCustomer($customer);
        $this->assertNotEmpty($customer->getId());

        $fetchedCustomer = $this->unzer->fetchCustomer($customer->getId());
        $exposeArray     = $customer->expose();
        $exposeArray['salutation'] = Salutations::UNKNOWN;
        $this->assertEquals($exposeArray, $fetchedCustomer->expose());

        return $customer;
    }

    /**
     * Max registered customer should be creatable.
     *
     * @test
     */
    public function maxRegisteredB2bCustomerCanBeCreatedAndFetched(): void
    {
        $customer = $this->getMaximalRegisteredB2bCustomer();
        $this->assertEmpty($customer->getId());
        $this->unzer->createCustomer($customer);
        $this->assertNotEmpty($customer->getId());

        $fetchedCustomer = $this->unzer->fetchCustomer($customer->getId());
        $this->assertEquals($customer->expose(), $fetchedCustomer->expose());
    }

    /**
     * Customer should contain clientIp set via header.
     *
     * @test
     */
    public function customerShouldContainClientIpSetViaHeader()
    {
        $customer = $this->getMinimalCustomer();
        $clientIp = '123.123.123.123';
        $this->unzer->setClientIp($clientIp);
        $this->unzer->createCustomer($customer);

        $fetchedCustomer = $this->unzer->fetchCustomer($customer->getId());
        $this->assertEquals($clientIp, $fetchedCustomer->getGeoLocation()->getClientIp());
    }

    //</editor-fold>
}
