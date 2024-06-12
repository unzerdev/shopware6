<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the Authorization transaction type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\TransactionTypes;

use UnzerSDK\Constants\RecurrenceTypes;
use UnzerSDK\Unzer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Sofort;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BasePaymentTest;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use stdClass;

class ChargeTest extends BasePaymentTest
{
    /**
     * Verify getters and setters.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkProperly(): void
    {
        $charge = new Charge();
        $this->assertNull($charge->getAmount());
        $this->assertNull($charge->getCurrency());
        $this->assertNull($charge->getReturnUrl());
        $this->assertNull($charge->isCard3ds());
        $this->assertEmpty($charge->getPaymentReference());

        // set data.
        $charge = new Charge(123.4, 'myCurrency', 'https://my-return-url.test');
        $charge->setCard3ds(true);
        $charge->setPaymentReference('my Payment Reference');

        // check data
        $this->assertEquals(123.4, $charge->getAmount());
        $this->assertEquals('myCurrency', $charge->getCurrency());
        $this->assertEquals('https://my-return-url.test', $charge->getReturnUrl());
        $this->assertTrue($charge->isCard3ds());
        $this->assertEquals('my Payment Reference', $charge->getPaymentReference());

        // update data.
        $charge->setAmount(567.8)->setCurrency('myNewCurrency')->setReturnUrl('https://another-return-url.test');
        $charge->setCard3ds(false);
        $charge->setPaymentReference('another Payment Reference');

        // check updated data
        $this->assertEquals(567.8, $charge->getAmount());
        $this->assertEquals('myNewCurrency', $charge->getCurrency());
        $this->assertEquals('https://another-return-url.test', $charge->getReturnUrl());
        $this->assertFalse($charge->isCard3ds());
        $this->assertEquals('another Payment Reference', $charge->getPaymentReference());
    }

    /**
     * Setting recurrence type without a payment type does not raise exception.
     *
     * @test
     */
    public function recurrenceTypeCanBeSetWithoutTypeParameter()
    {
        $charge = new Charge();

        $this->assertEmpty($charge->getAdditionalTransactionData());
        $this->assertEmpty($charge->getRecurrenceType());

        $charge->setRecurrenceType(RecurrenceTypes::ONE_CLICK);
    }

    /**
     * Verify that a Charge can be updated on handle response.
     *
     * @test
     */
    public function aChargeShouldBeUpdatedThroughResponseHandling(): void
    {
        $charge = new Charge();
        $this->assertNull($charge->getAmount());
        $this->assertNull($charge->getCurrency());
        $this->assertNull($charge->getReturnUrl());
        $this->assertNull($charge->getIban());
        $this->assertNull($charge->getBic());
        $this->assertNull($charge->getHolder());
        $this->assertNull($charge->getDescriptor());
        $this->assertNull($charge->getRecurrenceType());

        $charge = new Charge(123.4, 'myCurrency', 'https://my-return-url.test');
        $this->assertEquals(123.4, $charge->getAmount());
        $this->assertEquals('myCurrency', $charge->getCurrency());
        $this->assertEquals('https://my-return-url.test', $charge->getReturnUrl());

        $testResponse = new stdClass();
        $testResponse->amount = '789.0';
        $testResponse->currency = 'TestCurrency';
        $testResponse->returnUrl = 'https://return-url.test';
        $testResponse->Iban = 'DE89370400440532013000';
        $testResponse->Bic = 'COBADEFFXXX';
        $testResponse->Holder = 'Merchant Khang';
        $testResponse->Descriptor = '4065.6865.6416';
        $testResponse->additionalTransactionData = (object)['card' => (object)['recurrenceType' => 'oneClick']];

        $charge->handleResponse($testResponse);
        $this->assertEquals(789.0, $charge->getAmount());
        $this->assertEquals('TestCurrency', $charge->getCurrency());
        $this->assertEquals('https://return-url.test', $charge->getReturnUrl());
        $this->assertEquals('DE89370400440532013000', $charge->getIban());
        $this->assertEquals('COBADEFFXXX', $charge->getBic());
        $this->assertEquals('Merchant Khang', $charge->getHolder());
        $this->assertEquals('4065.6865.6416', $charge->getDescriptor());
    }

    /**
     * Verify response with empty account data can be handled.
     *
     * @test
     */
    public function verifyResponseWithEmptyAccountDataCanBeHandled()
    {
        $charge = new Charge();

        $testResponse = new stdClass();
        $testResponse->Iban = '';
        $testResponse->Bic = '';
        $testResponse->Holder = '';
        $testResponse->Descriptor = '';

        $charge->handleResponse($testResponse);
        $this->assertNull($charge->getIban());
        $this->assertNull($charge->getBic());
        $this->assertNull($charge->getHolder());
        $this->assertNull($charge->getDescriptor());
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

        (new Charge())->getLinkedResources();
    }

    /**
     * Verify linked resource.
     *
     * @test
     */
    public function getLinkedResourceShouldReturnResourcesBelongingToCharge(): void
    {
        $unzerObj    = new Unzer('s-priv-123345');
        $paymentType     = (new Sofort())->setId('123');
        $customer        = CustomerFactory::createCustomer('Max', 'Mustermann')->setId('123');
        $payment         = new Payment();
        $payment->setParentResource($unzerObj)->setPaymentType($paymentType)->setCustomer($customer);

        $charge          = (new Charge())->setPayment($payment);
        $linkedResources = $charge->getLinkedResources();
        $this->assertArrayHasKey('customer', $linkedResources);
        $this->assertArrayHasKey('type', $linkedResources);

        $this->assertSame($paymentType, $linkedResources['type']);
        $this->assertSame($customer, $linkedResources['customer']);
    }

    /**
     * Verify cancel() calls cancelCharge() on Unzer object with the given amount.
     *
     * @test
     */
    public function cancelShouldCallCancelChargeOnUnzerObject(): void
    {
        $charge =  new Charge();
        $unzerMock = $this->getMockBuilder(Unzer::class)
            ->disableOriginalConstructor()
            ->setMethods(['cancelCharge'])
            ->getMock();
        $unzerMock->expects($this->exactly(2))
            ->method('cancelCharge')->willReturn(new Cancellation())
            ->withConsecutive(
                [$this->identicalTo($charge), $this->isNull()],
                [$this->identicalTo($charge), 321.9]
            );

        /** @var Unzer $unzerMock */
        $charge->setParentResource($unzerMock);
        $charge->cancel();
        $charge->cancel(321.9);
    }

    /**
     * Verify getter for cancelled amount.
     *
     * @test
     */
    public function getCancelledAmountReturnsTheCancelledAmount(): void
    {
        $charge = new Charge();
        $this->assertEquals(0.0, $charge->getCancelledAmount());

        $charge = new Charge(123.4, 'myCurrency', 'https://my-return-url.test');
        $this->assertEquals(0.0, $charge->getCancelledAmount());

        $cancellationJson = '{
            "type": "cancel-charge",
            "status": "success",
            "amount": "10"
        }';

        $cancellation1 = new Cancellation();
        $cancellation1->handleResponse(json_decode($cancellationJson));
        $charge->addCancellation($cancellation1);
        $this->assertEquals(10.0, $charge->getCancelledAmount());

        $cancellation2 = new Cancellation();
        $cancellation2->handleResponse(json_decode($cancellationJson));
        $charge->addCancellation($cancellation2);
        $this->assertEquals(20.0, $charge->getCancelledAmount());
    }

    /**
     * Verify getter for total amount.
     *
     * @test
     */
    public function getTotalAmountReturnsAmountMinusCancelledAmount(): void
    {
        /** @var MockObject|Charge $chargeMock */
        $chargeMock = $this->getMockBuilder(Charge::class)
            ->setMethods(['getCancelledAmount'])
            ->setConstructorArgs([123.4, 'myCurrency', 'https://my-return-url.test'])
            ->getMock();

        $chargeMock->expects($this->exactly(3))->method('getCancelledAmount')
            ->willReturnOnConsecutiveCalls(0.0, 100.0, 123.4);

        $this->assertEquals(123.4, $chargeMock->getTotalAmount());
        $this->assertEquals(23.4, $chargeMock->getTotalAmount());
        $this->assertEquals(0.0, $chargeMock->getTotalAmount());
    }
}
