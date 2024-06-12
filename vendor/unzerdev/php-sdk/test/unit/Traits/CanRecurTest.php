<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the CanRecur trait.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Traits;

use UnzerSDK\Unzer;
use UnzerSDK\Resources\Recurring;
use UnzerSDK\test\BasePaymentTest;
use RuntimeException;
use stdClass;

class CanRecurTest extends BasePaymentTest
{
    /**
     * Verify setters and getters.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkProperly(): void
    {
        $dummy = new TraitDummyCanRecur();
        $this->assertFalse($dummy->isRecurring());
        $response = new stdClass();
        $response->recurring = true;
        $dummy->handleResponse($response);
        $this->assertTrue($dummy->isRecurring());
    }

    /**
     * Verify recurring activation on a resource which is not an abstract resource will throw an exception.
     *
     * @test
     */
    public function activateRecurringWillThrowExceptionIfTheObjectHasWrongType(): void
    {
        $dummy = new TraitDummyCanRecurNonResource();

        $this->expectException(RuntimeException::class);
        $dummy->activateRecurring('1234');
    }

    /**
     * Verify activation on object will call Unzer.
     *
     * @test
     */
    public function activateRecurringWillCallUnzerMethod(): void
    {
        $unzerMock = $this->getMockBuilder(Unzer::class)->disableOriginalConstructor()->setMethods(['activateRecurringPayment'])->getMock();

        /** @var Unzer $unzerMock */
        $dummy = (new TraitDummyCanRecur())->setParentResource($unzerMock);
        /** @noinspection PhpParamsInspection */
        $unzerMock->expects(self::once())->method('activateRecurringPayment')->with($dummy, 'return url')->willReturn(new Recurring('', ''));

        $dummy->activateRecurring('return url');
    }
}
