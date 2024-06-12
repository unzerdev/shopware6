<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and functionality of the Paypage.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\ExemptionType;
use UnzerSDK\Constants\RecurrenceTypes;
use UnzerSDK\Constants\TransactionTypes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\Paypage;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\Constants\PaymentState;

class PaypageTest extends BaseIntegrationTest
{
    // IDs for Expired payment page. IDs are testkeypair specific.
    private const EXPIRED_PAYPAGE_ID = "s-ppg-57cf4528728391347941b610ae22efccc92fa331d22e425a16b4e85b97c73362";
    private const PAYMENT_WITH_EXPIRED_PAYMENT_PAGE = 's-pay-328749';

    /**
     * Verify the Paypage resource for charge can be created with the mandatory parameters only.
     *
     * @test
     */
    public function minimalPaypageChargeShouldBeCreatableAndFetchable(): void
    {
        $paypage = new Paypage(100.0, 'EUR', self::RETURN_URL);
        $this->assertEmpty($paypage->getId());
        $paypage = $this->unzer->initPayPageCharge($paypage);
        $this->assertNotEmpty($paypage->getId());
    }

    /**
     * Verify fetching expired paypageId.
     *
     * @test
     */
    public function verifyFetchingExpiredPaypageThrowsException()
    {
        $this->expectException(UnzerApiException::class);
        $this->getUnzerObject()->fetchPayPage(self::EXPIRED_PAYPAGE_ID);
    }

    /**
     * Verify fetching payment that contains expired payment page is possible.
     *
     * @test
     */
    public function verifyFetchingPaymentwithExpiredPaymentPageIspossible()
    {
        $payment = $this->getUnzerObject()->fetchPayment(self::PAYMENT_WITH_EXPIRED_PAYMENT_PAGE);
        $this->assertEquals(self::EXPIRED_PAYPAGE_ID, $payment->getPayPage()->getId());
        $this->assertNotEmpty($payment->getFetchedAt());
    }

    /**
     * Verify the Paypage resource creates payment in state "create".
     *
     * @test
     */
    public function paymentShouldBeInStateCreateOnInitialization(): void
    {
        $paypage = new Paypage(100.0, 'EUR', self::RETURN_URL);
        $paypage = $this->unzer->initPayPageCharge($paypage);
        $payment = $paypage->getPayment();

        $this->assertTrue($payment->isCreate());
        $this->assertEquals($payment->getState(), PaymentState::STATE_CREATE);
    }

    /**
     * Verify the Paypage resource for charge can be created with all parameters.
     *
     * @test
     */
    public function maximumPaypageChargeShouldBeCreatable(): void
    {
        $orderId = 'o'. self::generateRandomId();
        $basket = $this->createBasket();
        $customer = CustomerFactory::createCustomer('Max', 'Mustermann');
        $invoiceId = 'i'. self::generateRandomId();
        $paypage = (new Paypage(119.0, 'EUR', self::RETURN_URL))
            ->setLogoImage('https://docs.unzer.com/card/card.png')
            ->setFullPageImage('https://docs.unzer.com/card/card.png')
            ->setShopName('My Test Shop')
            ->setShopDescription('Best shop in the whole world!')
            ->setTagline('Try and stop us from being awesome!')
            ->setOrderId($orderId)
            ->setTermsAndConditionUrl('https://www.unzer.com/en/')
            ->setPrivacyPolicyUrl('https://www.unzer.com/de/')
            ->setImprintUrl('https://www.unzer.com/it/')
            ->setHelpUrl('https://www.unzer.com/at/')
            ->setContactUrl('https://www.unzer.com/en/ueber-unzer/')
            ->setInvoiceId($invoiceId)
            ->setCard3ds(true)
            ->setEffectiveInterestRate(4.99)
            ->setCss([
                'shopDescription' => 'color: purple',
                'header' => 'background-color: red',
                'helpUrl' => 'color: blue',
                'contactUrl' => 'color: green',
            ]);
        $this->assertEmpty($paypage->getId());
        $paypage = $this->unzer->initPayPageCharge($paypage, $customer, $basket);
        $this->assertNotEmpty($paypage->getId());
        $this->assertEquals(4.99, $paypage->getEffectiveInterestRate());
        $payment = $paypage->getPayment();
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertNotNull($payment->getId());
        $this->assertNotEmpty($paypage->getRedirectUrl());
    }

    /**
     * Verify the Paypage resource for authorize can be created with the mandatory parameters only.
     *
     * @test
     */
    public function minimalPaypageAuthorizeShouldBeCreatableAndFetchable(): void
    {
        $paypage = new Paypage(100.0, 'EUR', self::RETURN_URL);
        $this->assertEmpty($paypage->getId());
        $paypage = $this->unzer->initPayPageAuthorize($paypage);
        $this->assertNotEmpty($paypage->getId());

        $fetchedPaypage = $this->unzer->fetchPayPage($paypage->getId());
        $this->assertEquals($paypage->getRedirectUrl(), $fetchedPaypage->getRedirectUrl());
        $this->assertEquals($paypage->getAction(), TransactionTypes::AUTHORIZATION);
    }

    /**
     * Custom additional transaction data can be set.
     *
     * @test
     */
    public function additionalAttributesCanBeSet(): void
    {
        $paypage = new Paypage(100.0, 'EUR', self::RETURN_URL);
        $paypage->setAdditionalAttribute('customField', 'customValue')
            ->setEffectiveInterestRate(4.99)
            ->setExemptionType(ExemptionType::LOW_VALUE_PAYMENT)
            ->setRecurrenceType(RecurrenceTypes::UNSCHEDULED);

        $paypage = $this->unzer->initPayPageAuthorize($paypage);
        $this->assertNotEmpty($paypage->getId());

        $fetchedPaypage = $this->unzer->fetchPayPage($paypage->getId());

        $this->assertEquals('customValue', $fetchedPaypage->getAdditionalAttribute('customField'));
        $this->assertEquals(4.99, $fetchedPaypage->getEffectiveInterestRate());
        $this->assertEquals(ExemptionType::LOW_VALUE_PAYMENT, $fetchedPaypage->getExemptionType());
        $this->assertEquals(RecurrenceTypes::UNSCHEDULED, $fetchedPaypage->getRecurrenceType());
    }

    /**
     * Verify fetched payment contains paypage when fetched.
     *
     * @test
     */
    public function fetchedPaymentShouldContainPayPageID(): void
    {
        $payPage = new Paypage(100.0, 'EUR', self::RETURN_URL);
        $this->assertEmpty($payPage->getId());
        $payPage = $this->unzer->initPayPageAuthorize($payPage);
        $payment = $payPage->getPayment();
        $this->assertNotEmpty($payPage->getId());

        $fetchedPayment = $this->unzer->fetchPayment($payPage->getPaymentId());
        $fetchedPayPage = $fetchedPayment->getPayPage();

        $this->assertNotEmpty($fetchedPayPage);
        $this->assertEquals($payPage->getId(), $fetchedPayPage->getId());
        $this->assertEquals($payment->expose(), $fetchedPayment->expose());
        $this->assertEmpty($fetchedPayment->getRedirectUrl());
    }

    /**
     * Verify the Paypage resource for authorize can be created with the mandatory parameters only.
     *
     * @test
     */
    public function fetchingPayPageShouldHaveReferenceToPayment(): void
    {
        $payPage = new Paypage(100.0, 'EUR', self::RETURN_URL);
        $this->assertEmpty($payPage->getId());

        $payPage = $this->unzer->initPayPageAuthorize($payPage);
        $payment = $payPage->getPayment();
        $this->assertNotEmpty($payPage->getId());

        $fetchedPayPage = $this->unzer->fetchPayPage($payPage->getId());
        $this->assertNotNull($fetchedPayPage->getPayment());
        $this->assertNotNull($fetchedPayPage->getRedirectUrl());

        $this->assertEquals($payment->getId(), $fetchedPayPage->getPayment()->getId());
        $this->assertNotNull($payment->getFetchedAt());
    }

    /**
     * Verify the Paypage resource for authorize can be created with all parameters.
     *
     * @test
     */
    public function maximumPaypageAuthorizeShouldBeCreatable(): void
    {
        $orderId = 'o'. self::generateRandomId();
        $basket = $this->createBasket();
        $customer = CustomerFactory::createCustomer('Max', 'Mustermann');
        $invoiceId = 'i'. self::generateRandomId();
        $paypage = (new Paypage(119.0, 'EUR', self::RETURN_URL))
            ->setLogoImage('https://docs.unzer.com/card/card.png')
            ->setFullPageImage('https://docs.unzer.com/card/card.png')
            ->setShopName('My Test Shop')
            ->setShopDescription('Best shop in the whole world!')
            ->setTagline('Try and stop us from being awesome!')
            ->setOrderId($orderId)
            ->setTermsAndConditionUrl('https://www.unzer.com/en/')
            ->setPrivacyPolicyUrl('https://www.unzer.com/de/')
            ->setImprintUrl('https://www.unzer.com/it/')
            ->setHelpUrl('https://www.unzer.com/at/')
            ->setContactUrl('https://www.unzer.com/en/ueber-unzer/')
            ->setInvoiceId($invoiceId)
            ->setCard3ds(true)
            ->setEffectiveInterestRate(4.99)
            ->setCss([
                'shopDescription' => 'color: purple',
                'header' => 'background-color: red',
                'helpUrl' => 'color: blue',
                'contactUrl' => 'color: green',
            ]);
        $paypage->addExcludeType(Card::getResourceName());
        $this->assertEmpty($paypage->getId());
        $paypage = $this->unzer->initPayPageAuthorize($paypage, $customer, $basket);
        $this->assertNotEmpty($paypage->getId());
        $this->assertEquals(4.99, $paypage->getEffectiveInterestRate());
        $this->assertEquals([Card::getResourceName()], $paypage->getExcludeTypes());
        $payment = $paypage->getPayment();
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertNotNull($payment->getId());
        $this->assertNotEmpty($paypage->getRedirectUrl());
    }

    /**
     * Validate paypage css can be set empty array.
     *
     * @test
     */
    public function cssShouldAllowForEmptyArray(): void
    {
        $paypage = new Paypage(100.0, 'EUR', self::RETURN_URL);
        $this->assertEmpty($paypage->getId());
        $paypage = $this->unzer->initPayPageAuthorize($paypage->setCss([]));
        $this->assertNotEmpty($paypage->getId());
    }
}
