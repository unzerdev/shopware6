<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the CanDirectCharge trait.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Traits;

use UnzerSDK\Unzer;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BasePaymentTest;
use RuntimeException;

class CanDirectChargeWithCustomerTest extends BasePaymentTest
{
    /**
     * Verify direct charge throws exception if the class does not implement the UnzerParentInterface.
     *
     * @test
     */
    public function directChargeShouldThrowExceptionIfTheClassDoesNotImplementParentInterface(): void
    {
        $dummy = new TraitDummyWithCustomerWithoutParentIF();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TraitDummyWithCustomerWithoutParentIF');

        $dummy->charge(1.0, 'MyCurrency', 'https://return.url', new Customer());
    }

    /**
     * Verify direct charge propagates to Unzer object.
     *
     * @test
     */
    public function directChargeShouldPropagateToUnzer(): void
    {
        $unzerMock = $this->getMockBuilder(Unzer::class)->setMethods(['charge'])->disableOriginalConstructor()->getMock();
        $dummyMock = $this->getMockBuilder(TraitDummyWithCustomerWithParentIF::class)->setMethods(['getUnzerObject'])->getMock();

        $charge = new Charge();
        $metadata  = new Metadata();
        $customer = (new Customer())->setId('123');
        $dummyMock->expects($this->exactly(3))->method('getUnzerObject')->willReturn($unzerMock);
        $unzerMock->expects($this->exactly(3))->method('charge')
            ->withConsecutive(
                [1.2, 'MyCurrency2', $dummyMock, 'https://return.url2', $customer, null],
                [1.3, 'MyCurrency3', $dummyMock, 'https://return.url3', $customer, 'orderId'],
                [1.4, 'MyCurrency4', $dummyMock, 'https://return.url4', $customer, 'orderId', $metadata]
            )->willReturn($charge);


        /** @var TraitDummyWithCustomerWithParentIF $dummyMock */
        $returnedCharge = $dummyMock->charge(1.2, 'MyCurrency2', 'https://return.url2', $customer);
        $this->assertSame($charge, $returnedCharge);
        $returnedCharge = $dummyMock->charge(1.3, 'MyCurrency3', 'https://return.url3', $customer, 'orderId');
        $this->assertSame($charge, $returnedCharge);
        $returnedCharge = $dummyMock->charge(1.4, 'MyCurrency4', 'https://return.url4', $customer, 'orderId', $metadata);
        $this->assertSame($charge, $returnedCharge);
    }
}
