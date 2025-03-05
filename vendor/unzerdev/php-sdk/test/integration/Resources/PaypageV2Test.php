<?php

namespace UnzerSDK\test\integration\Resources;

use UnzerSDK\Constants\ExemptionType;
use UnzerSDK\Constants\PaypageCheckoutTypes;
use UnzerSDK\Resources\EmbeddedResources\Paypage\PaymentMethodConfig;
use UnzerSDK\Resources\EmbeddedResources\Paypage\PaymentMethodsConfigs;
use UnzerSDK\Resources\EmbeddedResources\Paypage\Resources;
use UnzerSDK\Resources\EmbeddedResources\Paypage\Style;
use UnzerSDK\Resources\EmbeddedResources\Paypage\Urls;
use UnzerSDK\Resources\EmbeddedResources\Risk;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\PaymentTypes\Alipay;
use UnzerSDK\Resources\PaymentTypes\Applepay;
use UnzerSDK\Resources\PaymentTypes\Bancontact;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\EPS;
use UnzerSDK\Resources\PaymentTypes\Googlepay;
use UnzerSDK\Resources\PaymentTypes\Ideal;
use UnzerSDK\Resources\PaymentTypes\Klarna;
use UnzerSDK\Resources\PaymentTypes\OpenbankingPis;
use UnzerSDK\Resources\PaymentTypes\PaylaterDirectDebit;
use UnzerSDK\Resources\PaymentTypes\PaylaterInstallment;
use UnzerSDK\Resources\PaymentTypes\PaylaterInvoice;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\PaymentTypes\PayU;
use UnzerSDK\Resources\PaymentTypes\PostFinanceCard;
use UnzerSDK\Resources\PaymentTypes\PostFinanceEfinance;
use UnzerSDK\Resources\PaymentTypes\Prepayment;
use UnzerSDK\Resources\PaymentTypes\Przelewy24;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;
use UnzerSDK\Resources\PaymentTypes\Twint;
use UnzerSDK\Resources\PaymentTypes\Wechatpay;
use UnzerSDK\Resources\V2\Paypage;
use UnzerSDK\test\BaseIntegrationTest;

/**
 * @group CC-1309
 * @group CC-1377
 */
class PaypageV2Test extends BaseIntegrationTest
{
    /**
     * @test
     */
    public function createMinimumPaypageTest()
    {
        $paypage = new Paypage(9.99, 'EUR', 'charge');

        $this->assertNull($paypage->getId());
        $this->assertNull($paypage->getRedirectUrl());

        $this->getUnzerObject()->createPaypage($paypage);

        $this->assertCreatedPaypage($paypage);

        $this->assertNull($paypage->getType());
        $this->assertNull($paypage->getRecurrenceType());
        $this->assertNull($paypage->getShopName());
        $this->assertNull($paypage->getOrderId());
        $this->assertNull($paypage->getInvoiceId());
        $this->assertNull($paypage->getPaymentReference());

        $this->assertNull($paypage->getUrls());
        $this->assertNull($paypage->getStyle());
        $this->assertNull($paypage->getResources());
        $this->assertNull($paypage->getPaymentMethodsConfigs());
        $this->assertNull($paypage->getRisk());
    }

    /**
     * @test
     * @group skip
     */
    public function fetchPaypage()
    {
        $paypage = new Paypage(9.99, 'EUR', 'charge');
        $unzer = $this->getUnzerObject();
        $unzer->createPaypage($paypage);
        $this->assertCreatedPaypage($paypage);

        $unzer->fetchPaypageV2($paypage);
        $this->assertEquals($paypage->getPayments(), []);
        $this->assertEquals($paypage->getTotal(), 0);
    }

    /**
     * @test
     */
    public function JwtTokenShouldBeReusedForMultipleRequests()
    {
        $unzer = $this->getUnzerObject();

        $paypageFirst = new Paypage(9.99, 'EUR', 'charge');
        $paypageSecond = new Paypage(9.99, 'EUR', 'charge');

        $this->assertNull($paypageFirst->getId());
        $this->assertNull($paypageSecond->getRedirectUrl());

        // Create Firt paypage
        $this->assertNull($unzer->getJwtToken());
        $unzer->createPaypage($paypageFirst);
        $InitialJwtToken = $unzer->getJwtToken();
        $this->assertNotNull($InitialJwtToken);

        // Create Second paypage
        $unzer->createPaypage($paypageSecond);

        // JWT token has not changed.
        $this->assertEquals($InitialJwtToken, $unzer->getJwtToken());

        $this->assertCreatedPaypage($paypageFirst);
        $this->assertCreatedPaypage($paypageSecond);
    }

    /**
     * @test
     */
    public function createPaypageWithOptionalStringProperties()
    {
        $paypage = new Paypage(9.99, 'EUR', 'charge');
        $paypage->setType('hosted');
        $paypage->setRecurrenceType('unscheduled');
        $paypage->setShopName('shopName');
        $paypage->setOrderId('orderId');
        $paypage->setInvoiceId('invoiceId');
        $paypage->setPaymentReference('paymentReference');
        $paypage->setCheckoutType(PaypageCheckoutTypes::FULL);

        $this->getUnzerObject()->createPaypage($paypage);

        $this->assertCreatedPaypage($paypage);
    }

    /**
     * @test
     * @group skip
     */
    public function createPaypageWithUrls()
    {
        $urls = new Urls();
        $urls->setTermsAndCondition('https://termsandcondition.com');
        $urls->setPrivacyPolicy('https://privacypolicy.com');
        $urls->setImprint('https://imprint.com');
        $urls->setHelp('https://help.com');
        $urls->setContact('https://contact.com');
        $urls->setFavicon('https://favicon.com');
        $urls->setReturnSuccess('https://returnsuccess.com');
        $urls->setReturnPending('https://returnpending.com');
        $urls->setReturnFailure('https://returnfailure.com');
        $urls->setReturnCancel('https://returncancel.com');

        $paypage = new Paypage(9.99, 'EUR', 'charge');
        $paypage->setUrls($urls);

        $this->getUnzerObject()->createPaypage($paypage);

        $this->assertCreatedPaypage($paypage);
    }

    /**
     * @test
     * @group CC-1548
     * @group CC-1646
     */
    public function createPaypageWithStyle()
    {
        $style = new Style();
        $style
            ->setBackgroundColor('#1f1f1f')
            ->setBackgroundImage('https://backgroundimage.com')
            ->setBrandColor('#1f1f1f')
            ->setCornerRadius('5px')
            ->setFavicon('https://favicon.com')
            ->setFont('comic sans')
            ->setFooterColor('#1f1f1f')
            ->setHeaderColor('#ff7f7f')
            ->setHideBasket(true)
            ->setHideUnzerLogo(true)
            ->setLinkColor('#1f1f1f')
            ->setLogoImage('https://logoimage.com')
            ->setShadows(true)
            ->setTextColor('#1f1f1f');

        $paypage = new Paypage(9.99, 'EUR', 'charge');
        $paypage->setStyle($style);

        $this->getUnzerObject()->createPaypage($paypage);
        $this->assertCreatedPaypage($paypage);
    }

    /** @test
     * @dataProvider paymentMethodsConfigsDataProvider
     **/
    public function createPaypageWithMethodConfigs(PaymentMethodsConfigs $configs)
    {
        $paypage = new Paypage(9.99, 'EUR', 'charge');
        $paypage->setPaymentMethodsConfigs($configs);
        $this->getUnzerObject()->createPaypage($paypage);

        $this->assertCreatedPaypage($paypage);
    }

    /** @test * */
    public function createPaypageWithMethodResourceIds()
    {
        $unzer = $this->getUnzerObject();
        $customer = $unzer->createCustomer($this->getMinimalCustomer());
        $basket = $this->createV2Basket();
        $metadata = $unzer->createMetadata((new Metadata())->setShopType('unitTests'));


        $resources = new Resources($customer->getId(), $basket->getId(), $metadata->getId());
        $paypage = new Paypage(99.99, 'EUR', 'charge');
        $paypage->setResources($resources);
        $unzer->createPaypage($paypage);

        $this->assertCreatedPaypage($paypage);
    }

    /** @test
     */
    public function createPaypageWithRiskData()
    {
        $unzer = $this->getUnzerObject();
        $risk = new Risk();

        $risk->setCustomerGroup('neutral')
            ->setConfirmedAmount('1234')
            ->setConfirmedOrders('42')
            ->setRegistrationLevel('1')
            ->setRegistrationDate('20160412');

        $paypage = new Paypage(9.99, 'EUR', 'charge');
        $paypage->setRisk($risk);
        $unzer->createPaypage($paypage);

        $this->assertCreatedPaypage($paypage);
    }

    /**
     * @param Paypage $paypage
     * @return void
     */
    public function assertCreatedPaypage(Paypage $paypage): void
    {
        $this->assertNotNull($paypage->getId());
        $this->assertNotNull($paypage->getRedirectUrl());
        $this->assertStringContainsString($paypage->getId(), $paypage->getRedirectUrl());
    }

    public function paymentMethodsConfigsDataProvider()
    {
        $enabledConfig = new PaymentMethodConfig(true, 1);
        $paylaterConfig = (new PaymentMethodConfig(true, 1))
            ->setLabel('Paylater');
        $cardConfig = (new PaymentMethodConfig(true, 1))
            ->setCredentialOnFile(true)
            ->setExemption(ExemptionType::LOW_VALUE_PAYMENT);

        $withDefaultEnabled = (new PaymentMethodsConfigs())
            ->setDefault((new PaymentMethodConfig())->setEnabled(true));

        $withDefaultDisabled = (new PaymentMethodsConfigs())
            ->setDefault(
                (new PaymentMethodConfig())->setEnabled(false)
            )->setMethodConfigs(['paypal' => $enabledConfig]);

        $withMethodConfigs = new PaymentMethodsConfigs();
        $withMethodConfigs->setMethodConfigs([
            'paypal' => $enabledConfig
        ]);


        $withCardSpecificConfig = (new PaymentMethodsConfigs())->addMethodConfig(Card::class, $cardConfig);

        $withClassNames = (new PaymentMethodsConfigs())
            ->setDefault((new PaymentMethodConfig())->setEnabled(false))
            ->addMethodConfig(Card::class, $cardConfig)
            ->addMethodConfig(Paypal::class, $enabledConfig)
            ->addMethodConfig(PaylaterInstallment::class, $enabledConfig)
            ->addMethodConfig(Googlepay::class, $enabledConfig)
            ->addMethodConfig(Applepay::class, $enabledConfig)
            ->addMethodConfig(Klarna::class, $enabledConfig)
            ->addMethodConfig(SepaDirectDebit::class, $enabledConfig)
            ->addMethodConfig(EPS::class, $enabledConfig)
            ->addMethodConfig(PaylaterInvoice::class, $enabledConfig)
            ->addMethodConfig(PaylaterDirectDebit::class, $enabledConfig)
            ->addMethodConfig(Prepayment::class, $enabledConfig)
            ->addMethodConfig(PayU::class, $enabledConfig)
            ->addMethodConfig(Ideal::class, $enabledConfig)
            ->addMethodConfig(Przelewy24::class, $enabledConfig)
            ->addMethodConfig(Alipay::class, $enabledConfig)
            ->addMethodConfig(Wechatpay::class, $enabledConfig)
            ->addMethodConfig(Bancontact::class, $enabledConfig)
            ->addMethodConfig(PostFinanceEfinance::class, $enabledConfig)
            ->addMethodConfig(PostFinanceCard::class, $enabledConfig)
            ->addMethodConfig(Twint::class, $enabledConfig)
            ->addMethodConfig(OpenbankingPis::class, $enabledConfig)
        ;

        $withPaylaterConfig = (new PaymentMethodsConfigs())
            ->addMethodConfig(PaylaterInvoice::class, $paylaterConfig);

        return [
            'empty' => [new PaymentMethodsConfigs()],
            'Default Enabled' => [$withDefaultEnabled],
            'Default Disabled' => [$withDefaultDisabled],
            'Method Configs' => [$withMethodConfigs],
            'CardSpecificConfig' => [$withCardSpecificConfig],
            'ClassNames' => [$withClassNames],
            'PaylaterConfig' => [$withPaylaterConfig]
        ];
    }
}
