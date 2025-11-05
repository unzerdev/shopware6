<?php

namespace UnzerSDK\test\integration\Resources;

use UnzerSDK\Resources\EmbeddedResources\Paypage\AmountSettings;
use UnzerSDK\Resources\V2\Paypage;
use UnzerSDK\test\BaseIntegrationTest;

/**
 * @group CC-1316
 * @group CC-1578
 */
class LinkpayV2Test extends BaseIntegrationTest
{
    private static ?string $token = null;

    protected function setUp(): void
    {
        parent::setUp();
        self::$token = $this->unzer->prepareJwtToken(self::$token);
    }

    /**
     * @test
     */
    public function createMinimumPaypageTest()
    {
        $paypage = new Paypage(9.99, 'EUR', 'charge');
        $paypage->setType('linkpay');

        $this->assertNull($paypage->getId());
        $this->assertNull($paypage->getQrCodeSvg());
        $this->assertNull($paypage->getRedirectUrl());

        // when
        $this->getUnzerObject()->createPaypage($paypage);

        // then
        $this->assertCreatedPaypage($paypage);
        $this->assertEquals('linkpay', $paypage->getType());
        $this->assertNotNull($paypage->getQrCodeSvg());
    }

    /**
     * @test
     */
    public function deletePaypageTest()
    {
        $this->expectNotToPerformAssertions();
        $paypage = new Paypage(9.99, 'EUR', 'charge');
        $paypage->setType('linkpay');
        $this->getUnzerObject()->createPaypage($paypage);
        $paypageId = $paypage->getId();

        $this->getUnzerObject()->deletePaypage($paypage);
        $this->getUnzerObject()->fetchPaypageV2($paypageId);
    }

    /**
     * @test
     */
    public function deletePaypageWithInvalidId()
    {
        $this->expectException(\UnzerSDK\Exceptions\UnzerApiException::class);
        $this->getUnzerObject()->deletePaypageById('invalid-id');
    }

    /**
     * @test
     */
    public function deletePaypageByIDTest()
    {
        $this->expectNotToPerformAssertions();
        $paypage = new Paypage(9.99, 'EUR', 'charge');
        $paypage->setType('linkpay');
        $this->getUnzerObject()->createPaypage($paypage);
        $paypageId = $paypage->getId();

        $this->getUnzerObject()->deletePaypageById($paypageId);
        $this->getUnzerObject()->fetchPaypageV2($paypageId);
    }

    /**
     * @test
     */
    public function createLinkpayWithSpecificFields()
    {
        $paypage = new Paypage(null, 'EUR', 'charge');
        $paypage->setType('linkpay');
        $paypage->setAlias($this->generateRandomId());
        $paypage->setExpiresAt(new \DateTime());
        $paypage->setAmountSettings(new AmountSettings(10, 100));

        $this->assertNull($paypage->getId());
        $this->assertNull($paypage->getRedirectUrl());

        $this->getUnzerObject()->createPaypage($paypage);

        $this->assertCreatedPaypage($paypage);

        $this->assertEquals('linkpay', $paypage->getType());
    }

    /**
     * @test
     * @group CC-1583
     */
    public function updateLinkpayExpiryDate()
    {
        // init
        $unzer = $this->getUnzerObject();
        $paypage = new Paypage(null, 'EUR', 'charge');
        $paypage->setType('linkpay');
        $paypage->setAlias($this->generateRandomId());
        $interval = new \DateInterval("PT1H");
        $expiresAtInitial = new \DateTime('2024-09-25T16:39:01+00:00');
        $expiresUpdated = (new \DateTime('2024-09-25T16:39:01.1397+12:00'))->add($interval);

        $paypage->setExpiresAt($expiresAtInitial);
        $paypage->setAmountSettings(new AmountSettings(10, 100));

        // creation
        $unzer->createPaypage($paypage);
        $this->assertCreatedPaypage($paypage);
        $paypageId = $paypage->getId();

        // update
        $updatePaypage = new Paypage();
        $updatePaypage
            ->setId($paypageId)
            ->setExpiresAt($expiresUpdated);
        $unzer->patchPaypage($updatePaypage);
        $this->assertNotEquals($expiresUpdated, $updatePaypage->getExpiresAt()); // update response contains no milliseconds.

        $this->assertEquals('linkpay', $paypage->getType());
        $this->assertEquals($paypage->getCurrency(), $updatePaypage->getCurrency());
        $this->assertEquals($paypage->getAlias(), $updatePaypage->getAlias());
        $this->assertEquals($paypage->getAmountSettings(), $updatePaypage->getAmountSettings());

        $this->assertNotEmpty($updatePaypage->getResources()->getMetadataId());
    }

    /**
     * @test
     * @group CC-1583
     */
    public function updateLinkpayAmount()
    {
        // init
        $unzer = $this->getUnzerObject();
        $paypage = new Paypage(99.99, 'EUR', 'charge');
        $paypage->setType('linkpay');
        $paypage->setAlias($this->generateRandomId());
        $expiresAtInitial = new \DateTime('2024-09-25T16:39:01+00:00');

        $paypage->setExpiresAt($expiresAtInitial);

        // creation
        $unzer->createPaypage($paypage);
        $this->assertCreatedPaypage($paypage);
        $paypageId = $paypage->getId();

        // update
        $updatePaypage = new Paypage();
        $updatePaypage
            ->setId($paypageId)
            ->setAmount(66.66);

        $unzer->patchPaypage($updatePaypage);

        // updated value should be different
        $this->assertNotEquals($paypage->getAmount(), $updatePaypage->getAmount());

        // initial values should match
        $this->assertEquals('linkpay', $paypage->getType());
        $this->assertEquals($paypage->getCurrency(), $updatePaypage->getCurrency());
        $this->assertEquals($paypage->getAlias(), $updatePaypage->getAlias());
        $this->assertEquals($paypage->getAmountSettings(), $updatePaypage->getAmountSettings());
        $this->assertEquals($paypage->getExpiresAt(), $updatePaypage->getExpiresAt());

        $this->assertNotEmpty($updatePaypage->getResources()->getMetadataId());
        $this->assertEmpty($updatePaypage->getResources()->getCustomerId());
        $this->assertEmpty($updatePaypage->getResources()->getBasketId());
    }

    /**
     * @test
     * @group CC-1583
     */
    public function updateLinkpayAmountSettings()
    {
        // init
        $unzer = $this->getUnzerObject();
        $paypage = new Paypage(null, 'EUR', 'charge');
        $paypage->setType('linkpay');
        $paypage->setAlias($this->generateRandomId());
        $expiresAtInitial = new \DateTime('2024-09-25T16:39:01+00:00');

        $paypage->setExpiresAt($expiresAtInitial)
            ->setAmountSettings(new AmountSettings(9.99, 99.99));

        // creation
        $unzer->createPaypage($paypage);
        $this->assertCreatedPaypage($paypage);
        $paypageId = $paypage->getId();

        // update
        $updatePaypage = new Paypage();
        $updatePaypage
            ->setId($paypageId)
            ->setAmountSettings(new AmountSettings(6.66, 66.66));

        $unzer->patchPaypage($updatePaypage);

        // updated value should be different
        $this->assertNotEquals($paypage->getAmountSettings(), $updatePaypage->getAmountSettings());

        // initial values should match
        $this->assertEquals($paypage->getAlias(), $updatePaypage->getAlias());
        $this->assertEquals($paypage->getAmount(), $updatePaypage->getAmount());
        $this->assertEquals($paypage->getCurrency(), $updatePaypage->getCurrency());
        $this->assertEquals($paypage->getExpiresAt(), $updatePaypage->getExpiresAt());
        $this->assertEquals('linkpay', $paypage->getType());

        $this->assertNotEmpty($updatePaypage->getResources()->getMetadataId());
        $this->assertEmpty($updatePaypage->getResources()->getCustomerId());
        $this->assertEmpty($updatePaypage->getResources()->getBasketId());
    }

    /**
     * @param Paypage $paypage
     * @return void
     */
    public function assertCreatedPaypage(Paypage $paypage): void
    {
        $this->assertNotNull($paypage->getId());
        $this->assertNotNull($paypage->getRedirectUrl());
        if ($paypage->getAlias() == null) {
            $this->assertStringContainsString($paypage->getId(), $paypage->getRedirectUrl());
        } else {
            $this->assertStringContainsString($paypage->getAlias(), $paypage->getRedirectUrl());
        }
    }
}
