<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the Shipment transaction type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\TransactionTypes;

use UnzerSDK\Resources\TransactionTypes\Shipment;
use UnzerSDK\test\BasePaymentTest;
use stdClass;

class ShipmentTest extends BasePaymentTest
{
    /**
     * Verify getters and setters.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkProperly(): Shipment
    {
        $shipment = new Shipment();
        $this->assertNull($shipment->getAmount());
        $this->assertNull($shipment->getInvoiceId());

        $shipment->setAmount(123.4567);
        $shipment->setInvoiceId('NewInvoiceId');
        $this->assertEquals(123.4567, $shipment->getAmount());
        $this->assertEquals('NewInvoiceId', $shipment->getInvoiceId());

        return $shipment;
    }

    /**
     * Verify that an Shipment can be updated on handle response.
     *
     * @test
     *
     * @param Shipment $shipment
     *
     * @depends gettersAndSettersShouldWorkProperly
     */
    public function aShipmentShouldBeUpdatedThroughResponseHandling(Shipment $shipment): void
    {
        $testResponse = new stdClass();
        $testResponse->amount = '987.6543';
        $testResponse->invoiceId = 'AnotherInvoiceId';

        $shipment->handleResponse($testResponse);
        $this->assertEquals(987.6543, $shipment->getAmount());
        $this->assertEquals('AnotherInvoiceId', $shipment->getInvoiceId());
    }
}
