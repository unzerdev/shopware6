<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the Payout transaction type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\TransactionTypes;

use UnzerSDK\Unzer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Payout;
use UnzerSDK\test\BasePaymentTest;
use RuntimeException;
use stdClass;

class PayoutTest extends BasePaymentTest
{
    /**
     * Verify getters and setters.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkProperly(): void
    {
        $payout = new Payout();
        $this->assertNull($payout->getAmount());
        $this->assertNull($payout->getCurrency());
        $this->assertNull($payout->getReturnUrl());
        $this->assertNull($payout->getPaymentReference());

        $payout = new Payout(123.4, 'myCurrency', 'https://my-return-url.test');
        $payout->setPaymentReference('my payment reference');
        $this->assertEquals(123.4, $payout->getAmount());
        $this->assertEquals('myCurrency', $payout->getCurrency());
        $this->assertEquals('https://my-return-url.test', $payout->getReturnUrl());
        $this->assertEquals('my payment reference', $payout->getPaymentReference());

        $payout->setAmount(567.8)->setCurrency('myNewCurrency')->setReturnUrl('https://another-return-url.test');
        $payout->setPaymentReference('different payment reference');
        $this->assertEquals(567.8, $payout->getAmount());
        $this->assertEquals('myNewCurrency', $payout->getCurrency());
        $this->assertEquals('https://another-return-url.test', $payout->getReturnUrl());
        $this->assertEquals('different payment reference', $payout->getPaymentReference());
    }

    /**
     * Verify that an Payout can be updated on handle response.
     *
     * @test
     */
    public function aPayoutShouldBeUpdatedThroughResponseHandling(): void
    {
        $payout = new Payout();
        $this->assertNull($payout->getAmount());
        $this->assertNull($payout->getCurrency());
        $this->assertNull($payout->getReturnUrl());

        $payout = new Payout(123.4, 'myCurrency', 'https://my-return-url.test');
        $this->assertEquals(123.4, $payout->getAmount());
        $this->assertEquals('myCurrency', $payout->getCurrency());
        $this->assertEquals('https://my-return-url.test', $payout->getReturnUrl());

        $testResponse = new stdClass();
        $testResponse->amount = '789.0';
        $testResponse->currency = 'TestCurrency';
        $testResponse->returnUrl = 'https://return-url.test';

        $payout->handleResponse($testResponse);
        $this->assertEquals(789.0, $payout->getAmount());
        $this->assertEquals('TestCurrency', $payout->getCurrency());
        $this->assertEquals('https://return-url.test', $payout->getReturnUrl());
    }

    /**
     * Verify getLinkedResources throws exception if the paymentType is not set.
     *
     * @test
     */
    public function getLinkedResourcesShouldThrowExceptionWhenThePaymentTypeIsNotSet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payment type is missing!');

        (new Payout())->getLinkedResources();
    }

    /**
     * Verify linked resource.
     *
     * @test
     */
    public function getLinkedResourceShouldReturnResourcesBelongingToPayout(): void
    {
        $unzerObj = new Unzer('s-priv-123345');
        $paymentType = $this->createCardObject()->setId('123');
        $customer = CustomerFactory::createCustomer('Max', 'Mustermann')->setId('123');
        $payment = new Payment();
        $payment->setParentResource($unzerObj)->setPaymentType($paymentType)->setCustomer($customer);

        $payout = (new Payout())->setPayment($payment);
        $linkedResources = $payout->getLinkedResources();
        $this->assertArrayHasKey('customer', $linkedResources);
        $this->assertArrayHasKey('type', $linkedResources);

        $this->assertSame($paymentType, $linkedResources['type']);
        $this->assertSame($customer, $linkedResources['customer']);
    }
}
