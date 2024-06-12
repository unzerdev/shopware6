<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class verifies function of the instalment plan resources.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources;

use DateTime;
use UnzerSDK\Resources\InstalmentPlan;
use UnzerSDK\Resources\InstalmentPlans;
use UnzerSDK\test\BasePaymentTest;

class InstalmentPlanTest extends BasePaymentTest
{
    /**
     * Verify the functionalities of the instalment plan resources.
     *
     * @test
     *
     * @dataProvider verifyQueryStringDP
     *
     * @param float  $amount
     * @param string $currency
     * @param float  $effectiveInterest
     */
    public function verifyQueryString($amount, $currency, $effectiveInterest): void
    {
        $plans = new InstalmentPlans($amount, $currency, $effectiveInterest, new DateTime('13.11.2019'));
        $this->assertEquals("plans?amount={$amount}&currency={$currency}&effectiveInterest={$effectiveInterest}&orderDate=2019-11-13", $plans->getResourcePath());
    }

    /**
     * Verify getters and setters.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkAsExpected(): void
    {
        // when
        $instalmentPlans = new InstalmentPlans(1.234, 'EUR', 23.45);

        // then
        $this->assertEquals(1.234, $instalmentPlans->getAmount());
        $this->assertEquals('EUR', $instalmentPlans->getCurrency());
        $this->assertEquals(23.45, $instalmentPlans->getEffectiveInterest());
        $this->assertNull($instalmentPlans->getOrderDate());

        // when
        $instalmentPlans->setAmount(2.345)
            ->setCurrency('USD')
            ->setEffectiveInterest(34.56)
            ->setOrderDate($this->getTodaysDateString());

        // then
        $this->assertEquals(2.345, $instalmentPlans->getAmount());
        $this->assertEquals('USD', $instalmentPlans->getCurrency());
        $this->assertEquals(34.56, $instalmentPlans->getEffectiveInterest());
        $this->assertEquals($this->getTodaysDateString(), $instalmentPlans->getOrderDate());

        // when
        $instalmentPlans->setOrderDate($this->getYesterdaysTimestamp());

        // then
        $this->assertEquals($this->getYesterdaysTimestamp()->format('Y-m-d'), $instalmentPlans->getOrderDate());

        // when
        $instalmentPlans->setOrderDate(null);

        // then
        $this->assertNull($instalmentPlans->getOrderDate());
    }

    /**
     * Verify plans can be retrieved.
     *
     * @test
     */
    public function plansShouldBeRetrievable(): void
    {
        // when
        $instalmentPlans = new InstalmentPlans(1.234, 'EUR', 23.45);

        // then
        $this->assertEquals([], $instalmentPlans->getPlans());

        // when
        $plans = [(object)['orderDate' => 'plan 1'], (object)['orderDate' => 'plan 2']];
        $instalmentPlans->handleResponse((object)['entity' => (object)$plans]);

        // then
        $plans = $instalmentPlans->getPlans();
        $this->assertCount(2, $plans);

        /** @var InstalmentPlan $plan1 */
        /** @var InstalmentPlan $plan2 */
        [$plan1, $plan2] = $plans;
        $this->assertEquals('plan 1', $plan1->getOrderDate());
        $this->assertEquals('plan 2', $plan2->getOrderDate());
    }

    //<editor-fold desc="Data Providers">

    /**
     * @return array
     */
    public function verifyQueryStringDP(): array
    {
        return [
            [100, 'EUR', 4.99],
            [123.45, 'USD', 1.23]
        ];
    }

    //</editor-fold>
}
