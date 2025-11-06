<?php

namespace UnzerSDK\test\unit\Resources\EmbeddedResources;

use UnzerSDK\Constants\WeroAmountPaymentTypes;
use UnzerSDK\Constants\WeroCaptureTriggers;
use UnzerSDK\Resources\EmbeddedResources\WeroEventDependentPayment;
use UnzerSDK\Resources\EmbeddedResources\WeroTransactionData;
use UnzerSDK\test\BasePaymentTest;

class WeroTransactionDataTest extends BasePaymentTest
{
    /**
     * @test
     */
    public function gettersSettersAndExposeShouldWork(): void
    {
        $edp = (new WeroEventDependentPayment())
            ->setCaptureTrigger(WeroCaptureTriggers::SERVICEFULFILMENT)
            ->setAmountPaymentType(WeroAmountPaymentTypes::PAY)
            ->setMaxAuthToCaptureTime(300)
            ->setMultiCapturesAllowed(false);

        $wtd = (new WeroTransactionData())
            ->setEventDependentPayment($edp);

        // Getters
        $this->assertInstanceOf(WeroEventDependentPayment::class, $wtd->getEventDependentPayment());
        $this->assertEquals(WeroCaptureTriggers::SERVICEFULFILMENT, $wtd->getEventDependentPayment()->getCaptureTrigger());

        // Expose
        $exposed = $wtd->expose();
        $this->assertArrayHasKey('eventDependentPayment', $exposed);
        $this->assertEquals(WeroCaptureTriggers::SERVICEFULFILMENT, $exposed['eventDependentPayment']['captureTrigger']);
        $this->assertEquals(WeroAmountPaymentTypes::PAY, $exposed['eventDependentPayment']['amountPaymentType']);
        $this->assertSame(300, $exposed['eventDependentPayment']['maxAuthToCaptureTime']);
        $this->assertSame(false, $exposed['eventDependentPayment']['multiCapturesAllowed']);
        $this->assertArrayNotHasKey('enabled', $exposed);
    }
}
