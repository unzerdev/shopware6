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

use UnzerSDK\Unzer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Sofort;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BasePaymentTest;
use RuntimeException;

use function Webmozart\Assert\Tests\StaticAnalysis\object;

class AuthorizationTest extends BasePaymentTest
{
    /**
     * Verify getters and setters.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkProperly(): void
    {
        $authorization = new Authorization();
        $this->assertNull($authorization->getAmount());
        $this->assertNull($authorization->getCurrency());
        $this->assertNull($authorization->getReturnUrl());
        $this->assertNull($authorization->isCard3ds());
        $this->assertNull($authorization->getPaymentReference());

        $authorization = new Authorization(123.4, 'myCurrency', 'https://my-return-url.test');
        $authorization->setCard3ds(true)->setPaymentReference('my payment reference');
        $this->assertEquals(123.4, $authorization->getAmount());
        $this->assertEquals('myCurrency', $authorization->getCurrency());
        $this->assertEquals('https://my-return-url.test', $authorization->getReturnUrl());
        $this->assertEquals('my payment reference', $authorization->getPaymentReference());
        $this->assertTrue($authorization->isCard3ds());

        $authorization->setAmount(567.8)->setCurrency('myNewCurrency')->setReturnUrl('https://another-return-url.test');
        $authorization->setCard3ds(false)->setPaymentReference('different payment reference');
        $this->assertEquals(567.8, $authorization->getAmount());
        $this->assertEquals('myNewCurrency', $authorization->getCurrency());
        $this->assertEquals('https://another-return-url.test', $authorization->getReturnUrl());
        $this->assertEquals('different payment reference', $authorization->getPaymentReference());
        $this->assertFalse($authorization->isCard3ds());
    }

    /**
     * Verify that an Authorization can be updated on handle response.
     *
     * @test
     */
    public function anAuthorizationShouldBeUpdatedThroughResponseHandling(): void
    {
        $authorization = new Authorization();
        $this->assertNull($authorization->getAmount());
        $this->assertNull($authorization->getCurrency());
        $this->assertNull($authorization->getReturnUrl());
        $this->assertNull($authorization->getPDFLink());
        $this->assertNull($authorization->getZgReferenceId());
        $this->assertNull($authorization->getExternalOrderId());

        $authorization = new Authorization(123.4, 'myCurrency', 'https://my-return-url.test');
        $this->assertEquals(123.4, $authorization->getAmount());
        $this->assertEquals('myCurrency', $authorization->getCurrency());
        $this->assertEquals('https://my-return-url.test', $authorization->getReturnUrl());
        $this->assertNull($authorization->getPDFLink());
        $this->assertNull($authorization->getZgReferenceId());
        $this->assertNull($authorization->getExternalOrderId());

        $testResponse = [
            'amount' => '789.0',
            'currency' => 'TestCurrency',
            'returnUrl' => 'https://return-url.test',
            'PDFLink' => 'https://url.to.pdf',
            'zgReferenceId' => 'zg reference id',
            'externalOrderId' => 'external order id'
        ];

        $authorization->handleResponse((object)$testResponse);
        $this->assertEquals(789.0, $authorization->getAmount());
        $this->assertEquals('TestCurrency', $authorization->getCurrency());
        $this->assertEquals('https://return-url.test', $authorization->getReturnUrl());
        $this->assertEquals('https://url.to.pdf', $authorization->getPDFLink());
        $this->assertEquals('zg reference id', $authorization->getZgReferenceId());
        $this->assertEquals('external order id', $authorization->getExternalOrderId());
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

        (new Authorization())->getLinkedResources();
    }

    /**
     * Verify linked resource.
     *
     * @test
     */
    public function getLinkedResourceShouldReturnResourcesBelongingToAuthorization(): void
    {
        $unzerObj    = new Unzer('s-priv-123345');
        $paymentType     = (new Sofort())->setId('123');
        $customer        = CustomerFactory::createCustomer('Max', 'Mustermann')->setId('123');
        $payment         = new Payment();
        $payment->setParentResource($unzerObj)->setPaymentType($paymentType)->setCustomer($customer);

        $authorize       = (new Authorization())->setPayment($payment);
        $linkedResources = $authorize->getLinkedResources();
        $this->assertArrayHasKey('customer', $linkedResources);
        $this->assertArrayHasKey('type', $linkedResources);

        $this->assertSame($paymentType, $linkedResources['type']);
        $this->assertSame($customer, $linkedResources['customer']);
    }

    /**
     * Verify cancel() calls cancelAuthorization() on Unzer object with the given amount.
     *
     * @test
     */
    public function cancelShouldCallCancelAuthorizationOnUnzerObject(): void
    {
        $authorization =  new Authorization();
        $unzerMock = $this->getMockBuilder(Unzer::class)
            ->disableOriginalConstructor()
            ->setMethods(['cancelAuthorization'])
            ->getMock();
        $unzerMock->expects($this->exactly(2))
            ->method('cancelAuthorization')->willReturn(new Cancellation())
            ->withConsecutive(
                [$this->identicalTo($authorization), $this->isNull()],
                [$this->identicalTo($authorization), 321.9]
            );

        /** @var Unzer $unzerMock */
        $authorization->setParentResource($unzerMock);
        $authorization->cancel();
        $authorization->cancel(321.9);
    }

    /**
     * Verify charge throws exception if payment is not set.
     *
     * @test
     *
     * @dataProvider chargeValueProvider
     *
     * @param float|null $value
     */
    public function chargeShouldThrowExceptionIfPaymentIsNotSet($value): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payment object is missing. Try fetching the object first!');

        $authorization =  new Authorization();
        $authorization->charge($value);
    }

    /**
     * Verify charge() calls chargeAuthorization() on Unzer object with the given amount.
     *
     * @test
     */
    public function chargeShouldCallChargeAuthorizationOnUnzerObject(): void
    {
        $unzerMock = $this->getMockBuilder(Unzer::class)
            ->disableOriginalConstructor()
            ->setMethods(['chargeAuthorization'])
            ->getMock();
        /** @var Unzer $unzerMock */
        $payment = (new Payment())->setParentResource($unzerMock)->setId('myPayment');
        $unzerMock->expects($this->exactly(2))
            ->method('chargeAuthorization')->willReturn(new Charge())
            ->withConsecutive(
                [$this->identicalTo($payment), $this->isNull()],
                [$this->identicalTo($payment), 321.9]
            );

        $authorization =  new Authorization();
        $authorization->setPayment($payment);
        $authorization->charge();
        $authorization->charge(321.9);
    }

    /**
     * Verify getter for cancelled amount.
     *
     * @test
     */
    public function getCancelledAmountReturnsTheCancelledAmount(): void
    {
        $authorization = new Authorization();
        $this->assertEquals(0.0, $authorization->getCancelledAmount());

        $authorization = new Authorization(123.4, 'myCurrency', 'https://my-return-url.test');
        $this->assertEquals(0.0, $authorization->getCancelledAmount());

        $cancellation1 = new Cancellation(10.0);
        $authorization->addCancellation($cancellation1);
        $this->assertEquals(10.0, $authorization->getCancelledAmount());

        $cancellation2 = new Cancellation(10.0);
        $authorization->addCancellation($cancellation2);
        $this->assertEquals(20.0, $authorization->getCancelledAmount());
    }

    //<editor-fold desc="Data Providers">

    /**
     * Provide different amounts
     *
     * @return array
     */
    public function chargeValueProvider(): array
    {
        return [
            'Amount = null' => [null],
            'Amount = 0.0' => [0.0],
            'Amount = 123.8' => [123.8]
        ];
    }

    //</editor-fold>
}
