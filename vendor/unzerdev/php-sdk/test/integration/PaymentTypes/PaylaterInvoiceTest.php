<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and functionality of the payment method Paylater Invoice.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Resources\EmbeddedResources\RiskData;
use UnzerSDK\Resources\EmbeddedResources\ShippingData;
use UnzerSDK\Resources\PaymentTypes\PaylaterInvoice;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;

class PaylaterInvoiceTest extends BaseIntegrationTest
{
    /**
     * Verifies Invoice Secured payment type can be created.
     *
     * @test
     *
     * @return PaylaterInvoice
     */
    public function paylaterInvoiceTestTypeShouldBeCreatableAndFetchable(): PaylaterInvoice
    {
        /** @var PaylaterInvoice $invoice */
        $invoice = $this->unzer->createPaymentType(new PaylaterInvoice());
        $this->assertInstanceOf(PaylaterInvoice::class, $invoice);
        $this->assertNotNull($invoice->getId());

        $fetchedInvoice = $this->unzer->fetchPaymentType($invoice->getId());
        $this->assertInstanceOf(PaylaterInvoice::class, $fetchedInvoice);
        $this->assertEquals($invoice->getId(), $fetchedInvoice->getId());
        $this->assertMatchesRegularExpression('/^s-piv-[.]*/', $fetchedInvoice->getId());

        return $invoice;
    }

    /**
     * Customer should contain clientIp set via header.
     *
     * @test
     */
    public function clientIpCanBeManuallySetForPaylaterInvoiceType()
    {
        $clientIp = '123.123.123.123';
        $this->unzer->setClientIp($clientIp);

        /** @var PaylaterInvoice $invoice */
        $invoice = $this->unzer->createPaymentType(new PaylaterInvoice());
        $fetchedInvoice = $this->unzer->fetchPaymentType($invoice->getId());

        $this->assertEquals($clientIp, $fetchedInvoice->getGeoLocation()->getClientIp());
    }

    /**
     * Verify that paylater Invoice type can be authorized.
     *
     * @test
     *
     * @depends paylaterInvoiceTestTypeShouldBeCreatableAndFetchable
     *
     * @param mixed $paylaterInvoice
     */
    public function paylaterInvoiceCanbeAuthorized($paylaterInvoice): Authorization
    {
        $authorization = new Authorization(99.99, 'EUR', 'https://unzer.com');
        $authorization->setInvoiceId('202205021237');

        $customer = $this->getMaximumCustomerInclShippingAddress();
        $basket = $this->createV2Basket();

        $authorization = $this->unzer->performAuthorization($authorization, $paylaterInvoice, $customer, null, $basket);
        $this->assertNotEmpty($authorization->getId());
        $this->assertTrue($authorization->isSuccess());

        return $authorization;
    }

    /**
     * Verify that paylater Invoice type can be authorized with riskdata.
     *
     * @test
     *
     * @depends paylaterInvoiceTestTypeShouldBeCreatableAndFetchable
     *
     * @param mixed $paylaterInvoice
     */
    public function paylaterInvoiceCanbeAuthorizedWithRiskData($paylaterInvoice): Authorization
    {
        $riskData = new RiskData();
        $riskData->setThreatMetrixId('f544if49wo4f74ef1x')
            ->setCustomerGroup('TOP')
            ->setCustomerId('C-122345')
            ->setConfirmedAmount('1234')
            ->setConfirmedOrders('42')
            ->setRegistrationLevel('1')
            ->setRegistrationDate('20160412');

        $authorization = new Authorization(99.99, 'EUR', 'https://unzer.com');
        $authorization->setRiskData($riskData)
            ->setInvoiceId('202205021237');

        $customer = $this->getMaximumCustomerInclShippingAddress();
        $basket = $this->createV2Basket();

        $transaction = $this->unzer->performAuthorization($authorization, $paylaterInvoice, $customer, null, $basket);
        $this->assertNotEmpty($transaction->getId());
        $this->assertNotEmpty($transaction->getRiskData());
        $this->assertEquals($authorization->getAdditionalTransactionData(), $transaction->getAdditionalTransactionData());
        $this->assertTrue($transaction->isSuccess());

        return $authorization;
    }

    /**
     * Verify that paylater Invoice type can be authorized with not registered B2B Customer.
     *
     * @test
     *
     * @depends paylaterInvoiceTestTypeShouldBeCreatableAndFetchable
     *
     * @param mixed $paylaterInvoice
     */
    public function paylaterInvoiceCanbeAuthorizedWithB2BCustomer($paylaterInvoice)
    {
        $authorization = new Authorization(99.99, 'EUR', 'https://unzer.com');
        $authorization->setInvoiceId('202205021237');

        $customer = $this->getMaximalNotRegisteredB2bCustomer();
        $basket = $this->createV2Basket();

        $transaction = $this->unzer->performAuthorization($authorization, $paylaterInvoice, $customer, null, $basket);
        $this->assertNotEmpty($transaction->getId());
        $this->assertNotEmpty($transaction->getDescriptor());
        $this->assertTrue($transaction->isSuccess());
    }

    /**
     * Verify that paylater Invoice type can be charged.
     *
     * @test
     *
     * @depends paylaterInvoiceCanbeAuthorized
     *
     * @param mixed $paylaterInvoice
     * @param mixed $authorization
     */
    public function paylaterInvoiceCanbeCharged($authorization)
    {
        $charge = $this->unzer->performChargeOnPayment($authorization->getPayment(), new Charge(99.99));

        $this->assertNotEmpty($charge->getId());
        $this->assertTrue($charge->isSuccess());
    }

    /**
     * Verify that paylater Invoice type can be charged with shippingdata.
     *
     * @test
     *
     * @param mixed $paylaterInvoice
     * @param mixed $authorization
     */
    public function paylaterInvoiceCanbeChargedWithShippingData()
    {
        $authorization = $this->createPaylaterInvoiceAuthorization();
        $shippingData = (object)[
            "deliveryTrackingId" => "00340434286851877897",
            "deliveryService" => "DHL",
            "returnTrackingId" => "00340434286851877900"
        ];
        $shipping = new ShippingData();
        $shipping->handleResponse($shippingData);

        $chargeInstance = new Charge(99.99);
        $chargeInstance->setOrderId($this->generateRandomId())
            ->setInvoiceId($this->generateRandomId())
            ->setPaymentReference('reference')
            ->setShipping($shipping);

        $chargeResponse = $this->unzer->performChargeOnPayment($authorization->getPayment(), $chargeInstance);
        $this->assertNotEmpty($chargeResponse->getId());
        $this->assertTrue($chargeResponse->isSuccess());

        $fetchedCharge = $this->unzer->fetchChargeById($authorization->getPaymentId(), $chargeResponse->getId());
        $this->assertEquals($shipping, $fetchedCharge->getShipping());
    }
}
