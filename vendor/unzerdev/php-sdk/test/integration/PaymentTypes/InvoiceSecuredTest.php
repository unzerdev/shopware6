<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and functionality of the payment method Invoice Secured.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\InvoiceSecured;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\test\Helper\TestEnvironmentService;

/**
 * @deprecated since 1.2.0.0 PaylaterInvoice should be used instead in the future.
 */
class InvoiceSecuredTest extends BaseIntegrationTest
{
    protected function setUp(): void
    {
        $this->getUnzerObject(TestEnvironmentService::getLegacyTestPrivateKey());
    }

    /**
     * Verifies Invoice Secured payment type can be created.
     *
     * @test
     *
     * @return InvoiceSecured
     */
    public function invoiceSecuredTypeShouldBeCreatableAndFetchable(): InvoiceSecured
    {
        /** @var InvoiceSecured $invoice */
        $invoice = $this->unzer->createPaymentType(new InvoiceSecured());
        $this->assertInstanceOf(InvoiceSecured::class, $invoice);
        $this->assertNotNull($invoice->getId());

        $fetchedInvoice = $this->unzer->fetchPaymentType($invoice->getId());
        $this->assertInstanceOf(InvoiceSecured::class, $fetchedInvoice);
        $this->assertEquals($invoice->getId(), $fetchedInvoice->getId());

        return $invoice;
    }

    /**
     * Verify Invoice Secured is not authorizable.
     *
     * @test
     *
     * @param InvoiceSecured $invoice
     *
     * @depends invoiceSecuredTypeShouldBeCreatableAndFetchable
     */
    public function verifyInvoiceIsNotAuthorizable(InvoiceSecured $invoice): void
    {
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED);

        $this->unzer->authorize(1.0, 'EUR', $invoice, self::RETURN_URL);
    }

    /**
     * Verify Invoice Secured needs a customer object
     *
     * @test
     *
     * @depends invoiceSecuredTypeShouldBeCreatableAndFetchable
     *
     * @param InvoiceSecured $invoiceSecured
     */
    public function invoiceSecuredShouldRequiresCustomer(InvoiceSecured $invoiceSecured): void
    {
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_FACTORING_REQUIRES_CUSTOMER);
        $this->unzer->charge(1.0, 'EUR', $invoiceSecured, self::RETURN_URL);
    }

    /**
     * Verify Invoice Secured is chargeable.
     *
     * @test
     *
     * @depends invoiceSecuredTypeShouldBeCreatableAndFetchable
     *
     * @param InvoiceSecured $invoiceSecured
     */
    public function invoiceSecuredRequiresBasket(InvoiceSecured $invoiceSecured): void
    {
        $customer = $this->getMaximumCustomer();
        $customer->setShippingAddress($customer->getBillingAddress());

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_FACTORING_REQUIRES_BASKET);

        $invoiceSecured->charge(1.0, 'EUR', self::RETURN_URL, $customer);
    }

    /**
     * Verify charge with Invoice Secured throws an error when invalid ip is set.
     *
     * @test
     */
    public function invoiceSecuredRequiresValidClientIpForCharge(): void
    {
        $clientIp = '123.456.789.123';
        $this->unzer->setClientIp($clientIp);

        /** @var InvoiceSecured $invoiceSecured */
        $invoiceSecured = $this->unzer->createPaymentType(new InvoiceSecured());
        $this->unzer->setClientIp(null); // Ensure that the invalid ip is only used for type creation.
        $this->assertEquals($clientIp, $invoiceSecured->getGeoLocation()->getClientIp());

        $customer = $this->getMaximumCustomer();
        $customer->setShippingAddress($customer->getBillingAddress());
        $basket = $this->createBasket();

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::CORE_INVALID_IP_NUMBER);

        $invoiceSecured->charge(119.0, 'EUR', self::RETURN_URL, $customer, $basket->getOrderId(), null, $basket);
    }

    /**
     * Verify creating a payment type with client ip header set, will overwrite the clientIp of the API resource.
     *
     * @test
     */
    public function verifySettingClientIpViaHeaderWillOverwriteClientIpOfTypeResource(): void
    {
        $clientIp = 'xxx.xxx.xxx.xxx';
        $this->unzer->setClientIp($clientIp);

        /** @var InvoiceSecured $invoiceSecured */
        $invoiceSecured = $this->unzer->createPaymentType(new InvoiceSecured());
        $this->assertEquals($clientIp, $invoiceSecured->getGeoLocation()->getClientIp());
    }

    /**
     * Verify Invoice Secured is chargeable.
     *
     * @test
     *
     * @depends invoiceSecuredTypeShouldBeCreatableAndFetchable
     *
     * @param InvoiceSecured $invoiceSecured
     *
     * @return Charge
     */
    public function invoiceSecuredShouldBeChargeable(InvoiceSecured $invoiceSecured): Charge
    {
        $customer = $this->getMaximumCustomer();
        $customer->setShippingAddress($customer->getBillingAddress());

        $basket = $this->createBasket();
        $charge = $invoiceSecured->charge(119.0, 'EUR', self::RETURN_URL, $customer, $basket->getOrderId(), null, $basket);
        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
        $this->assertNotEmpty($charge->getIban());
        $this->assertNotEmpty($charge->getBic());
        $this->assertNotEmpty($charge->getHolder());
        $this->assertNotEmpty($charge->getDescriptor());

        return $charge;
    }

    /**
     * Verify Invoice Secured is not shippable on Unzer object.
     *
     * @test
     */
    public function verifyInvoiceSecuredIsNotShippableWoInvoiceIdOnUnzerObject(): void
    {
        // create payment type
        /** @var InvoiceSecured $invoiceSecured */
        $invoiceSecured = $this->unzer->createPaymentType(new InvoiceSecured());

        // perform charge
        $customer = $this->getMaximumCustomer();
        $customer->setShippingAddress($customer->getBillingAddress());

        $basket = $this->createBasket();
        $charge = $invoiceSecured->charge(119.0, 'EUR', self::RETURN_URL, $customer, $basket->getOrderId(), null, $basket);

        // perform shipment
        $payment = $charge->getPayment();
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_SHIPPING_REQUIRES_INVOICE_ID);
        $this->unzer->ship($payment);
    }

    /**
     * Verify Invoice Secured is not shippable on payment object.
     *
     * @test
     */
    public function verifyInvoiceSecuredIsNotShippableWoInvoiceIdOnPaymentObject(): void
    {
        // create payment type
        /** @var InvoiceSecured $invoiceSecured */
        $invoiceSecured = $this->unzer->createPaymentType(new InvoiceSecured());

        // perform charge
        $customer = $this->getMaximumCustomer();
        $customer->setShippingAddress($customer->getBillingAddress());

        $basket = $this->createBasket();
        $charge = $invoiceSecured->charge(119.0, 'EUR', self::RETURN_URL, $customer, $basket->getOrderId(), null, $basket);

        // perform shipment
        $payment = $charge->getPayment();
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_SHIPPING_REQUIRES_INVOICE_ID);
        $payment->ship();
    }

    /**
     * Verify Invoice Secured shipment with invoice id on Unzer object.
     *
     * @test
     */
    public function verifyInvoiceSecuredShipmentWithInvoiceIdOnUnzerObject(): void
    {
        // create payment type
        /** @var InvoiceSecured $invoiceSecured */
        $invoiceSecured = $this->unzer->createPaymentType(new InvoiceSecured());

        // perform charge
        $customer = $this->getMaximumCustomer();
        $customer->setShippingAddress($customer->getBillingAddress());

        $basket = $this->createBasket();
        $charge = $invoiceSecured->charge(119.0, 'EUR', self::RETURN_URL, $customer, $basket->getOrderId(), null, $basket);

        // perform shipment
        $payment   = $charge->getPayment();
        $invoiceId = 'i' . self::generateRandomId();
        $shipment  = $this->unzer->ship($payment, $invoiceId);
        // expect Payment to be completed after shipment.
        $this->assertTrue($shipment->getPayment()->isCompleted());
        $this->assertNotNull($shipment->getId());
        $this->assertEquals($invoiceId, $shipment->getInvoiceId());
    }

    /**
     * Verify Invoice Secured shipment with invoice id on payment object.
     *
     * @test
     */
    public function verifyInvoiceSecuredShipmentWithInvoiceIdOnPaymentObject(): void
    {
        // create payment type
        /** @var InvoiceSecured $invoiceSecured */
        $invoiceSecured = $this->unzer->createPaymentType(new InvoiceSecured());

        // perform charge
        $customer = $this->getMaximumCustomer();
        $customer->setShippingAddress($customer->getBillingAddress());

        $basket = $this->createBasket();
        $charge = $invoiceSecured->charge(119.0, 'EUR', self::RETURN_URL, $customer, $basket->getOrderId(), null, $basket);

        $payment   = $charge->getPayment();
        $invoiceId = 'i' . self::generateRandomId();
        $shipment  = $payment->ship($invoiceId);
        $this->assertNotNull($shipment->getId());
        $this->assertEquals($invoiceId, $shipment->getInvoiceId());
    }

    /**
     * Verify Invoice Secured shipment with pre set invoice id
     *
     * @test
     */
    public function verifyInvoiceSecuredShipmentWithPreSetInvoiceId(): void
    {
        /** @var InvoiceSecured $invoiceSecured */
        $invoiceSecured = $this->unzer->createPaymentType(new InvoiceSecured());

        $customer = $this->getMaximumCustomer();
        $customer->setShippingAddress($customer->getBillingAddress());

        $basket = $this->createBasket();
        $invoiceId = 'i' . self::generateRandomId();
        $charge = $invoiceSecured->charge(119.0, 'EUR', self::RETURN_URL, $customer, $basket->getOrderId(), null, $basket, null, $invoiceId);

        $payment   = $charge->getPayment();
        $shipment  = $this->unzer->ship($payment);
        $this->assertNotNull($shipment->getId());
        $this->assertEquals($invoiceId, $shipment->getInvoiceId());
    }

    /**
     * Verify Invoice Secured charge can canceled.
     *
     * @test
     *
     * @param Charge $charge
     *
     * @depends invoiceSecuredShouldBeChargeable
     */
    public function verifyInvoiceChargeCanBeCanceled(Charge $charge): Charge
    {
        $cancellation = $charge->cancel(100, CancelReasonCodes::REASON_CODE_CANCEL);
        $this->assertNotNull($cancellation);
        $this->assertNotNull($cancellation->getId());
        return $charge;
    }

    /**
     * Verify Invoice Secured charge cancel throws exception if the amount is missing.
     *
     * @test
     *
     * @param Charge $charge
     *
     * @depends verifyInvoiceChargeCanBeCanceled
     */
    public function verifyInvoiceChargeCanBeCancelledWoAmount(Charge $charge): void
    {
        $cancellation = $charge->cancel(null, CancelReasonCodes::REASON_CODE_CANCEL);

        $this->assertNotNull($cancellation);
        $this->assertNotNull($cancellation->getId());
        $this->assertEquals(19.0, $cancellation->getAmount());
    }

    /**
     * Verify Invoice Secured charge cancel throws exception if the reason is missing.
     *
     * @test
     *
     * @param Charge $charge
     *
     * @depends invoiceSecuredShouldBeChargeable
     */
    public function verifyInvoiceChargeCanNotBeCancelledWoReasonCode(Charge $charge): void
    {
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_CANCEL_REASON_CODE_IS_MISSING);
        $charge->cancel(100.0);
    }
}
