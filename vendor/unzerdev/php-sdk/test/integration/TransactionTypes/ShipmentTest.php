<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and functionality of shipment.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\TransactionTypes;

use UnzerSDK\Resources\PaymentTypes\InvoiceSecured;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\test\Helper\TestEnvironmentService;

class ShipmentTest extends BaseIntegrationTest
{
    protected function setUp(): void
    {
        $this->getUnzerObject(TestEnvironmentService::getLegacyTestPrivateKey());
    }

    /**
     * Verify shipment transaction can be called.
     *
     * @test
     */
    public function shipmentShouldBeCreatableAndFetchable(): void
    {
        $ivg      = new InvoiceSecured();
        $customer = $this->getMaximumCustomerInclShippingAddress()->setShippingAddress($this->getBillingAddress());

        $basket = $this->createBasket();
        $charge   = $this->unzer->charge(100.0, 'EUR', $ivg, self::RETURN_URL, $customer, null, null, $basket);
        $this->assertNotNull($charge->getId());
        $this->assertNotNull($charge);

        $shipment = $this->unzer->ship($charge->getPayment(), 'i'. self::generateRandomId(), 'i'. self::generateRandomId());
        $this->assertNotNull($shipment->getId());
        $this->assertNotNull($shipment);

        $fetchedShipment = $this->unzer->fetchShipment($shipment->getPayment()->getId(), $shipment->getId());
        $this->assertNotEmpty($fetchedShipment);
        $this->assertEquals($shipment->expose(), $fetchedShipment->expose());
    }

    /**
     * Verify shipment transaction can be called on the payment object.
     *
     * @test
     */
    public function shipmentCanBeCalledOnThePaymentObject(): void
    {
        $invoiceSecured = new InvoiceSecured();
        $customer          = $this->getMaximumCustomerInclShippingAddress()->setShippingAddress($this->getBillingAddress());
        $basket = $this->createBasket();
        $charge = $this->unzer->charge(100.0, 'EUR', $invoiceSecured, self::RETURN_URL, $customer, null, null, $basket);

        $payment  = $charge->getPayment();
        $shipment = $payment->ship('i'. self::generateRandomId(), 'o'. self::generateRandomId());
        $this->assertNotNull($shipment);
        $this->assertNotEmpty($shipment->getId());
        $this->assertNotEmpty($shipment->getUniqueId());
        $this->assertNotEmpty($shipment->getShortId());

        $traceId = $shipment->getTraceId();
        $this->assertNotEmpty($traceId);
        $this->assertSame($traceId, $shipment->getPayment()->getTraceId());

        $fetchedShipment = $this->unzer->fetchShipment($shipment->getPayment()->getId(), $shipment->getId());
        $this->assertNotEmpty($fetchedShipment);
        $this->assertEquals($shipment->expose(), $fetchedShipment->expose());
    }

    /**
     * Verify shipment can be performed with payment object.
     *
     * @test
     */
    public function shipmentShouldBePossibleWithPaymentObject(): void
    {
        $invoiceSecured = new InvoiceSecured();
        $customer          = $this->getMaximumCustomerInclShippingAddress()->setShippingAddress($this->getBillingAddress());
        $basket = $this->createBasket();
        $charge = $this->unzer->charge(100.0, 'EUR', $invoiceSecured, self::RETURN_URL, $customer, null, null, $basket);

        $payment  = $charge->getPayment();
        $shipment = $this->unzer->ship($payment, 'i'. self::generateRandomId(), 'o'. self::generateRandomId());
        $this->assertNotNull($shipment->getId());
        $this->assertNotNull($shipment);
    }

    /**
     * Verify transaction status.
     *
     * @test
     */
    public function shipmentStatusIsSetCorrectly(): void
    {
        $invoiceSecured = new InvoiceSecured();
        $customer          = $this->getMaximumCustomerInclShippingAddress()->setShippingAddress($this->getBillingAddress());
        $basket = $this->createBasket();
        $charge = $this->unzer->charge(100.0, 'EUR', $invoiceSecured, self::RETURN_URL, $customer, null, null, $basket);

        $payment  = $charge->getPayment();
        $shipment = $this->unzer->ship($payment, 'i'. self::generateRandomId(), 'o'. self::generateRandomId());
        $this->assertSuccess($shipment);
    }
}
