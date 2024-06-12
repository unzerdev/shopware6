<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the HasCancellations trait.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Traits;

use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\test\BasePaymentTest;

class HasCancellationsTest extends BasePaymentTest
{
    /**
     * Verify getters setters.
     *
     * @test
     */
    public function hasCancellationGettersAndSettersShouldWorkProperly(): void
    {
        $dummy = new TraitDummyHasCancellationsHasPaymentState();
        $this->assertIsEmptyArray($dummy->getCancellations());

        // assert getCancellation
        $this->assertNull($dummy->getCancellation('3'));

        // assert addCancellation
        $cancellation1 = (new Cancellation())->setId('1');
        $cancellation2 = (new Cancellation())->setId('2');
        $cancellation3 = (new Cancellation())->setId('3');
        $dummy->addCancellation($cancellation1);
        $dummy->addCancellation($cancellation2);
        $dummy->addCancellation($cancellation3);
        $this->assertEquals([$cancellation1, $cancellation2, $cancellation3], $dummy->getCancellations());

        // assert getCancellation
        $this->assertSame($cancellation3, $dummy->getCancellation('3', true));

        // assert setCancellations
        $cancellation4 = (new Cancellation())->setId('4');
        $cancellation5 = (new Cancellation())->setId('5');
        $cancellation6 = (new Cancellation())->setId('6');
        $dummy->setCancellations([$cancellation4, $cancellation5, $cancellation6]);
        $this->assertEquals([$cancellation4, $cancellation5, $cancellation6], $dummy->getCancellations());
    }

    /**
     * Verify getCancellation will call getResource with the selected Cancellation if it is not lazy loaded.
     *
     * @test
     */
    public function getCancellationShouldCallGetResourceIfItIsNotLazyLoaded(): void
    {
        $cancel = (new Cancellation())->setId('myCancelId');
        $authorizeMock = $this->getMockBuilder(Authorization::class)->setMethods(['getResource'])->getMock();
        /** @noinspection PhpParamsInspection */
        $authorizeMock->expects($this->once())->method('getResource')->with($cancel);

        /** @var Authorization $authorizeMock */
        $authorizeMock->addCancellation($cancel);
        $this->assertSame($authorizeMock, $cancel->getParentResource());
        $this->assertSame($cancel, $authorizeMock->getCancellation('myCancelId'));
    }
}
