<?php
/*
 *  Tests for additional transaction data trait
 *
 *  @link  https://docs.unzer.com/
 *
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */

namespace UnzerSDK\test\unit\Traits;

use UnzerSDK\Constants\WeroAmountPaymentTypes;
use UnzerSDK\Constants\WeroCaptureTriggers;
use UnzerSDK\Resources\EmbeddedResources\CardTransactionData;
use UnzerSDK\Resources\EmbeddedResources\RiskData;
use UnzerSDK\Resources\EmbeddedResources\ShippingData;
use UnzerSDK\Resources\EmbeddedResources\WeroEventDependentPayment;
use UnzerSDK\Resources\EmbeddedResources\WeroTransactionData;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\test\BasePaymentTest;

class HasAdditionalTransactionDataTest extends BasePaymentTest
{
    /**
     * Verify setters and getters.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkProperly(): void
    {
        $dummy = new TraitDummyHasAdditionalTransactionData();

        $this->assertNull($dummy->getShipping());
        $this->assertNull($dummy->getRiskData());
        $this->assertNull($dummy->getPrivacyPolicyUrl());
        $this->assertNull($dummy->getTermsAndConditionUrl());
        $this->assertNull($dummy->getCheckoutType());

        $shipping = (new ShippingData())
            ->setDeliveryService('deliveryService')
            ->setDeliveryTrackingId('deliveryTrackingId')
            ->setReturnTrackingId('returnTrackingId');

        $riskData = (new RiskData())
            ->setThreatMetrixId('threatMetrixId');

        $privacyPolicyUrl = 'privacyPolicyUrl';
        $termsAndConditionUrl = 'termsAndConditionUrl';
        $dummy->setShipping($shipping)
            ->setRiskData($riskData)
            ->setPrivacyPolicyUrl($privacyPolicyUrl)
            ->setCheckoutType('express', new Paypal())
            ->setTermsAndConditionUrl($termsAndConditionUrl);

        $this->assertNotNull($dummy->getShipping());
        $this->assertNotNull($dummy->getRiskData());
        $this->assertNotNull($dummy->getPrivacyPolicyUrl());
        $this->assertNotNull($dummy->getTermsAndConditionUrl());
        $this->assertNotNull($dummy->getCheckoutType());

        $this->assertEquals($shipping, $dummy->getShipping());
        $this->assertEquals($riskData, $dummy->getRiskData());
        $this->assertEquals($privacyPolicyUrl, $dummy->getPrivacyPolicyUrl());
        $this->assertEquals($termsAndConditionUrl, $dummy->getTermsAndConditionUrl());
    }

    /**
     * Setting and getting card data should work as expected.
     *
     * @test
     */
    public function setAndGetCardData(): void
    {
        $dummy = new TraitDummyHasAdditionalTransactionData();
        $this->assertNull($dummy->getCardTransactionData());

        $cardTransactionData = (new CardTransactionData())
            ->setRecurrenceType('recurrenceType')
            ->setExemptionType('exemptionType');

        $dummy->setCardTransactionData($cardTransactionData);

        $cardData = $dummy->getCardTransactionData();
        $this->assertNotNull($cardData);
        $this->assertEquals('exemptionType', $cardData->getExemptionType());
        $this->assertNull($cardData->getLiability());
        $this->assertEquals('recurrenceType', $cardData->getRecurrenceType());
    }

    /**
     * CardData should be exposed correctly.
     *
     * @test
     */
    public function exposeCardDataAsExpected(): void
    {
        $dummy = new TraitDummyHasAdditionalTransactionData();
        $this->assertNull($dummy->getCardTransactionData());

        $cardTransactionData = (new CardTransactionData())
            ->setRecurrenceType('recurrenceType')
            ->setExemptionType('exemptionType');

        $dummy->setCardTransactionData($cardTransactionData);

        $exposedResource = $dummy->expose();
        $this->assertNotNull($exposedResource['additionalTransactionData']);
        $additionalTransactionData = $exposedResource['additionalTransactionData'];

        $this->assertFalse(isset($additionalTransactionData->card['liability']));
        $this->assertEquals('recurrenceType', $additionalTransactionData->card['recurrenceType']);
        $this->assertEquals('exemptionType', $additionalTransactionData->card['exemptionType']);
    }

    /**
     * WeroData setters/getters should work as expected.
     *
     * @test
     */
    public function setAndGetWeroData(): void
    {
        $dummy = new TraitDummyHasAdditionalTransactionData();
        $this->assertNull($dummy->getWeroTransactionData());

        $edp = (new WeroEventDependentPayment())
            ->setCaptureTrigger(WeroCaptureTriggers::SERVICEFULFILMENT)
            ->setAmountPaymentType(WeroAmountPaymentTypes::PAY)
            ->setMaxAuthToCaptureTime(300)
            ->setMultiCapturesAllowed(false);

        $weroTransactionData = (new WeroTransactionData())
            ->setEventDependentPayment($edp);

        $dummy->setWeroTransactionData($weroTransactionData);

        $wero = $dummy->getWeroTransactionData();
        $this->assertNotNull($wero);
        $this->assertInstanceOf(WeroTransactionData::class, $wero);
        $this->assertInstanceOf(WeroEventDependentPayment::class, $wero->getEventDependentPayment());
        $this->assertEquals(WeroCaptureTriggers::SERVICEFULFILMENT, $wero->getEventDependentPayment()->getCaptureTrigger());
        $this->assertEquals(WeroAmountPaymentTypes::PAY, $wero->getEventDependentPayment()->getAmountPaymentType());
        $this->assertSame(300, $wero->getEventDependentPayment()->getMaxAuthToCaptureTime());
        $this->assertSame(false, $wero->getEventDependentPayment()->getMultiCapturesAllowed());
    }

    /**
     * WeroData should be exposed correctly.
     *
     * @test
     */
    public function exposeWeroDataAsExpected(): void
    {
        $dummy = new TraitDummyHasAdditionalTransactionData();
        $this->assertNull($dummy->getWeroTransactionData());

        $edp = (new WeroEventDependentPayment())
            ->setCaptureTrigger(WeroCaptureTriggers::SERVICEFULFILMENT)
            ->setAmountPaymentType(WeroAmountPaymentTypes::PAY)
            ->setMaxAuthToCaptureTime(300)
            ->setMultiCapturesAllowed(false);

        $weroTransactionData = (new WeroTransactionData())
            ->setEventDependentPayment($edp);

        $dummy->setWeroTransactionData($weroTransactionData);

        $exposedResource = $dummy->expose();
        $this->assertNotNull($exposedResource['additionalTransactionData']);
        $additionalTransactionData = $exposedResource['additionalTransactionData'];

        $this->assertArrayHasKey('eventDependentPayment', $additionalTransactionData->wero);
        $this->assertEquals(WeroCaptureTriggers::SERVICEFULFILMENT, $additionalTransactionData->wero['eventDependentPayment']['captureTrigger']);
        $this->assertEquals(WeroAmountPaymentTypes::PAY, $additionalTransactionData->wero['eventDependentPayment']['amountPaymentType']);
        $this->assertSame(300, $additionalTransactionData->wero['eventDependentPayment']['maxAuthToCaptureTime']);
        $this->assertSame(false, $additionalTransactionData->wero['eventDependentPayment']['multiCapturesAllowed']);
    }

    /**
     * Verify checkoutType can be set via typeId correctly.
     *
     * @test
     */
    public function checkoutTypeCanBeSetViaTypeId(): void
    {
        $dummy = new TraitDummyHasAdditionalTransactionData();
        $dummy->setCheckoutType('checkoutType', 's-ppl-xyz');

        $additionalTransactionData = $dummy->getAdditionalTransactionData();
        $this->assertTrue(property_exists($additionalTransactionData, 'paypal'));
        $this->assertEquals($additionalTransactionData->paypal->checkoutType, 'checkoutType');
    }

    /**
     * @test
     */
    public function getterShouldReturnNullIfAdittionalTransactionDataDoNOtContainProperObject()
    {
        $dummy = new TraitDummyHasAdditionalTransactionData();
        $dummy->addAdditionalTransactionData('shipping', 'This is not a shippingObject!');
        $dummy->addAdditionalTransactionData('riskData', 'This is not a riskDataObject!');

        $this->assertEquals('This is not a shippingObject!', $dummy->getAdditionalTransactionData()->shipping);
        $this->assertEquals('This is not a riskDataObject!', $dummy->getAdditionalTransactionData()->riskData);

        $this->assertNull($dummy->getShipping());
        $this->assertNull($dummy->getRiskData());
    }
}
