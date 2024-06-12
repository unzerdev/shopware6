<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the PayPage feature.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Constants\TransactionTypes;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\Giropay;
use UnzerSDK\Resources\PaymentTypes\Paypage;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;
use UnzerSDK\Services\ResourceService;
use UnzerSDK\test\BasePaymentTest;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

class PayPageTest extends BasePaymentTest
{
    /**
     * Verify setter and getter work.
     *
     * @test
     */
    public function getterAndSetterWorkAsExpected(): void
    {
        // ----------- SET initial values ------------
        $paypage = new Paypage(123.4, 'EUR', 'https://dev.unzer.com');

        // ----------- VERIFY initial values ------------
        $this->assertEquals(123.4, $paypage->getAmount());
        $this->assertEquals('EUR', $paypage->getCurrency());
        $this->assertEquals('https://dev.unzer.com', $paypage->getReturnUrl());

        // meta
        $this->assertNull($paypage->getPaymentId());
        $this->assertNull($paypage->getPayment());
        $this->assertEquals(TransactionTypes::CHARGE, $paypage->getAction());
        $this->assertNull($paypage->getRedirectUrl());

        // layout and design
        $this->assertNull($paypage->getFullPageImage());
        $this->assertNull($paypage->getLogoImage());
        $this->assertNull($paypage->getShopDescription());
        $this->assertNull($paypage->getShopName());
        $this->assertNull($paypage->getTagline());
        $this->assertNull($paypage->getCss());

        // link urls
        $this->assertNull($paypage->getContactUrl());
        $this->assertNull($paypage->getHelpUrl());
        $this->assertNull($paypage->getImprintUrl());
        $this->assertNull($paypage->getPrivacyPolicyUrl());
        $this->assertNull($paypage->getTermsAndConditionUrl());

        // other
        $this->assertCount(0, $paypage->getExcludeTypes());
        $this->assertNull($paypage->isCard3ds());

        // ----------- SET test values ------------
        $payment = (new Payment())->setId('my payment id');
        $paypage
            ->setAmount(321.0)
            ->setCurrency('CHF')
            ->setReturnUrl('my return url')
            ->setAction(TransactionTypes::AUTHORIZATION)
            ->setFullPageImage('full page image')
            ->setLogoImage('logo image')
            ->setShopDescription('my shop description')
            ->setShopName('my shop name')
            ->setTagline('my shops tag line')
            ->setContactUrl('my contact url')
            ->setHelpUrl('my help url')
            ->setImprintUrl('my imprint url')
            ->setPrivacyPolicyUrl('my privacy policy url')
            ->setTermsAndConditionUrl('my tac url')
            ->setPayment($payment)
            ->setRedirectUrl('https://redirect.url')
            ->addExcludeType(SepaDirectDebit::getResourceName())
            ->setCard3ds(true)
            ->setCss([
                         'shopDescription' => 'color: purple',
                         'header' => 'background-color: red',
                         'helpUrl' => 'color: blue',
                         'contactUrl' => 'color: green',
                     ]);

        // ----------- VERIFY test values ------------
        $this->assertEquals(321.0, $paypage->getAmount());
        $this->assertEquals('CHF', $paypage->getCurrency());
        $this->assertEquals('my return url', $paypage->getReturnUrl());

        // meta
        $this->assertEquals(TransactionTypes::AUTHORIZATION, $paypage->getAction());
        $this->assertEquals('my payment id', $paypage->getPaymentId());
        $this->assertSame($payment, $paypage->getPayment());
        $this->assertEquals('https://redirect.url', $paypage->getRedirectUrl());

        // layout and design
        $this->assertEquals('full page image', $paypage->getFullPageImage());
        $this->assertEquals('logo image', $paypage->getLogoImage());
        $this->assertEquals('my shop description', $paypage->getShopDescription());
        $this->assertEquals('my shop name', $paypage->getShopName());
        $this->assertEquals('my shops tag line', $paypage->getTagline());
        $this->assertEquals([
                                'shopDescription' => 'color: purple',
                                'header' => 'background-color: red',
                                'helpUrl' => 'color: blue',
                                'contactUrl' => 'color: green',
                            ], $paypage->getCss());

        // link urls
        $this->assertEquals('my contact url', $paypage->getContactUrl());
        $this->assertEquals('my help url', $paypage->getHelpUrl());
        $this->assertEquals('my imprint url', $paypage->getImprintUrl());
        $this->assertEquals('my privacy policy url', $paypage->getPrivacyPolicyUrl());
        $this->assertEquals('my tac url', $paypage->getTermsAndConditionUrl());

        // other
        $this->assertEquals([SepaDirectDebit::getResourceName()], $paypage->getExcludeTypes());
        $paypage->setExcludeTypes([Card::getResourceName(), Giropay::getResourceName()]);
        $this->assertEquals([Card::getResourceName(), Giropay::getResourceName()], $paypage->getExcludeTypes());
        $this->assertTrue($paypage->isCard3ds());

        // SET test values 2
        $paypage->setCard3ds(false);
        $this->assertFalse($paypage->isCard3ds());
    }

    /**
     * Verify handling of response and property setters/getters.
     *
     * @test
     */
    public function responseHandlingShouldWorkProperly(): void
    {
        // when
        $paypage = new Paypage(123.4, 'EUR', 'https://dev.unzer.com');
        $payment = new Payment();
        $paypage->setPayment($payment);

        // then
        $this->assertEquals(123.4, $paypage->getAmount());
        $this->assertEquals('EUR', $paypage->getCurrency());
        $this->assertEquals('https://dev.unzer.com', $paypage->getReturnUrl());

        $this->assertNull($paypage->getPaymentId());
        $this->assertSame($payment, $paypage->getPayment());
        $this->assertEquals(TransactionTypes::CHARGE, $paypage->getAction());
        $this->assertNull($paypage->getRedirectUrl());

        $this->assertNull($paypage->getFullPageImage());
        $this->assertNull($paypage->getLogoImage());
        $this->assertNull($paypage->getShopDescription());
        $this->assertNull($paypage->getShopName());
        $this->assertNull($paypage->getTagline());
        $this->assertNull($paypage->getCss());

        $this->assertNull($paypage->getContactUrl());
        $this->assertNull($paypage->getHelpUrl());
        $this->assertNull($paypage->getImprintUrl());
        $this->assertNull($paypage->getPrivacyPolicyUrl());
        $this->assertNull($paypage->getTermsAndConditionUrl());

        // when
        $response = new stdClass();
        $response->amount = 765.4;
        $response->currency = 'CHF';
        $response->returnUrl = 'another return url';
        $response->action = TransactionTypes::AUTHORIZATION;
        $response->redirectUrl = 'redirect url';
        $response->fullPageImage = 'full page image';
        $response->logoImage = 'logo image';
        $response->shopDescription = 'shop description';
        $response->shopName = 'shop name';
        $response->tagline = 'tagline';
        $response->contactUrl = 'contact url';
        $response->helpUrl = 'help url';
        $response->imprintUrl = 'imprint url';
        $response->privacyPolicyUrl = 'privacy policy url';
        $response->termsAndConditionUrl = 'tac url';
        $response->css = ['my' => 'styles'];
        $paypage->handleResponse($response);

        // then
        $this->assertEquals(765.4, $paypage->getAmount());
        $this->assertEquals('CHF', $paypage->getCurrency());
        $this->assertEquals('another return url', $paypage->getReturnUrl());

        $this->assertSame($payment, $paypage->getPayment());
        $this->assertEquals(TransactionTypes::AUTHORIZATION, $paypage->getAction());
        $this->assertEquals('redirect url', $paypage->getRedirectUrl());

        $this->assertEquals('full page image', $paypage->getFullPageImage());
        $this->assertEquals('logo image', $paypage->getLogoImage());
        $this->assertEquals('shop description', $paypage->getShopDescription());
        $this->assertEquals('shop name', $paypage->getShopName());
        $this->assertEquals('tagline', $paypage->getTagline());
        $this->assertEquals(['my' => 'styles'], $paypage->getCss());

        $this->assertEquals('contact url', $paypage->getContactUrl());
        $this->assertEquals('help url', $paypage->getHelpUrl());
        $this->assertEquals('imprint url', $paypage->getImprintUrl());
        $this->assertEquals('privacy policy url', $paypage->getPrivacyPolicyUrl());
        $this->assertEquals('tac url', $paypage->getTermsAndConditionUrl());
    }

    /**
     * Verify handling of payment object.
     *
     * @test
     */
    public function paymentObjectShouldBeUpdatedProperly(): void
    {
        // when
        $paypage = new Paypage(123.4, 'EUR', 'https://dev.unzer.com');
        $payment = new Payment();
        $paypage->setPayment($payment);

        // then
        $this->assertNull($paypage->getPaymentId());
        $this->assertSame($payment, $paypage->getPayment());

        // when
        $payment->setId('test id');

        // then
        $this->assertEquals('test id', $paypage->getPaymentId());

        // when
        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->paymentId = 'new payment id';
        $paypage->handleResponse($response);

        // then
        $this->assertEquals('new payment id', $paypage->getPaymentId());
    }

    /**
     * Verify handling of response in case of special fields.
     *
     * @test
     */
    public function responseHandlingShouldMapSpecialFieldsProperly(): void
    {
        // when
        $paypage = new Paypage(123.4, 'EUR', 'https://dev.unzer.com');

        $response = new stdClass();
        $response->impressumUrl = 'impressum url';
        $paypage->handleResponse($response);

        // then
        $this->assertEquals('impressum url', $paypage->getImprintUrl());
    }

    /**
     * Verify payment is fetched if it is no GET request.
     *
     * @test
     *
     * @dataProvider paymentShouldBeFetchedWhenItIsNoGetRequestDP
     *
     * @param string $method
     * @param mixed  $fetchCallCount
     */
    public function paymentShouldBeFetchedWhenItIsNoGetRequest($method, $fetchCallCount): void
    {
        // mock resource service to check whether fetch is called on it with the payment object.
        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['fetchResource'])->getMock();

        // when
        $paypage = new Paypage(123.4, 'EUR', 'https://dev.unzer.com');
        $payment = (new Payment())->setParentResource($this->unzer->setResourceService($resourceSrvMock));
        $paypage->setPayment($payment)->setParentResource($payment);

        // should
        /** @noinspection PhpParamsInspection */
        $resourceSrvMock->expects($this->exactly($fetchCallCount))->method('fetchResource')->with($payment);

        // when
        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->paymentId = 'payment id';
        $paypage->handleResponse($response, $method);
    }

    /**
     * Verify expose behaves as expected.
     *
     * @test
     */
    public function exposeShouldSetBasicParams(): void
    {
        // when
        $basket = (new Basket())->setId('basketId');
        $customer = (new Customer())->setId('customerId');
        $metadata = (new Metadata())->setId('metadataId');
        $payment = (new Payment())
            ->setParentResource($this->unzer)
            ->setId('my payment id')
            ->setBasket($basket)
            ->setMetadata($metadata)
            ->setCustomer($customer);
        $paypage = (new Paypage(123.4567, 'EUR', self::RETURN_URL))
            ->setFullPageImage('full page image')
            ->setParentResource($payment)
            ->setLogoImage('logo image')
            ->setShopDescription('my shop description')
            ->setShopName('my shop name')
            ->setTagline('my shops tag line')
            ->setContactUrl('my contact url')
            ->setHelpUrl('my help url')
            ->setImprintUrl('my imprint url')
            ->setPrivacyPolicyUrl('my privacy policy url')
            ->setTermsAndConditionUrl('my tac url')
            ->setPayment($payment)
            ->setRedirectUrl('https://redirect.url')
            ->setOrderId('my order id')
            ->setInvoiceId('my invoice id')
            ->setEffectiveInterestRate(4.99)
            ->setCss(['my' => 'style']);

        // then
        $expected = [
            'resources' => [
                'basketId' => 'basketId',
                'metadataId' => 'metadataId',
                'paymentId' => 'my payment id',
                'customerId' => 'customerId'
            ],
            'amount' => 123.4567,
            'currency' => 'EUR',
            'returnUrl' => self::RETURN_URL,
            'fullPageImage' => 'full page image',
            'logoImage' => 'logo image',
            'shopDescription' => 'my shop description',
            'shopName' => 'my shop name',
            'tagline' => 'my shops tag line',
            'contactUrl' => 'my contact url',
            'helpUrl' => 'my help url',
            'impressumUrl' => 'my imprint url',
            'privacyPolicyUrl' => 'my privacy policy url',
            'termsAndConditionUrl' => 'my tac url',
            'orderId' => 'my order id',
            'invoiceId' => 'my invoice id',
            'excludeTypes' => [],
            'additionalAttributes' => ['effectiveInterestRate' => 4.99],
            'css' => ['my' => 'style']
        ];
        $this->assertEquals($expected, $paypage->expose());
    }

    /**
     * Verify resources are returned as null if no payment object exists.
     *
     * @test
     */
    public function resourcesAreNullWithoutPaymentObject(): void
    {
        // when
        $paypage = new Paypage(123.4567, 'EUR', self::RETURN_URL);

        // then
        $this->assertNull($paypage->getPayment());
        $this->assertNull($paypage->getCustomer());
        $this->assertNull($paypage->getMetadata());
        $this->assertNull($paypage->getBasket());

        // when
        $basket = (new Basket())->setId('basketId');
        $customer = (new Customer())->setId('customerId');
        $metadata = (new Metadata())->setId('metadataId');
        $payment = (new Payment())->setParentResource($this->unzer)->setBasket($basket)->setMetadata($metadata)->setCustomer($customer);
        $paypage->setPayment($payment);

        // then
        $this->assertSame($payment, $paypage->getPayment());
        $this->assertSame($payment->getCustomer(), $paypage->getCustomer());
        $this->assertSame($payment->getMetadata(), $paypage->getMetadata());
        $this->assertSame($payment->getBasket(), $paypage->getBasket());
    }

    //<editor-fold desc="DataProvider">

    /**
     * @return array
     */
    public function paymentShouldBeFetchedWhenItIsNoGetRequestDP(): array
    {
        return [
            'GET' => [HttpAdapterInterface::REQUEST_GET, 0],
            'PUT' => [HttpAdapterInterface::REQUEST_PUT, 1],
            'DELETE' => [HttpAdapterInterface::REQUEST_DELETE, 1],
            'POST' => [HttpAdapterInterface::REQUEST_POST, 1]
        ];
    }

    //</editor-fold>
}
