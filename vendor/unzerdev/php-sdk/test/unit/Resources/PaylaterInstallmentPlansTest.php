<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class verifies function of the paylater installment plan resources.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources;

use UnzerSDK\Constants\CustomerTypes;
use UnzerSDK\Resources\EmbeddedResources\Paylater\InstallmentPlansQuery;
use UnzerSDK\Resources\EmbeddedResources\Paylater\InstallmentRate;
use UnzerSDK\Resources\EmbeddedResources\Paylater\InstallmentPlan;
use UnzerSDK\Resources\PaylaterInstallmentPlans;
use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\test\Fixtures\JsonProvider;

class PaylaterInstallmentPlansTest extends BasePaymentTest
{
    /**
     * Verify getters and setters.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkAsExpected(): void
    {
        // when
        $PaylaterInstallmentPlans = new PaylaterInstallmentPlans();

        // then
        $this->assertNull($PaylaterInstallmentPlans->getAmount());
        $this->assertNull($PaylaterInstallmentPlans->getCurrency());
        $this->assertNull($PaylaterInstallmentPlans->getCountry());
        $this->assertNull($PaylaterInstallmentPlans->getCustomerType());

        // when
        $PaylaterInstallmentPlans->setAmount(2.345)
            ->setCurrency('USD')
            ->setCountry('DE')
            ->setCustomerType(CustomerTypes::B2C);

        // then
        $this->assertEquals(2.345, $PaylaterInstallmentPlans->getAmount());
        $this->assertEquals('USD', $PaylaterInstallmentPlans->getCurrency());
        $this->assertEquals('DE', $PaylaterInstallmentPlans->getCountry());
        $this->assertEquals(CustomerTypes::B2C, $PaylaterInstallmentPlans->getCustomerType());
    }

    /**
     * Verify the functionalities of the instalment plan resources.
     *
     * @test
     *
     * @dataProvider verifyQueryStringDP
     *
     * @param float  $amount
     * @param string $currency
     * @param string $country
     * @param string $customerType
     */
    public function verifyQueryString(float $amount, string $currency, string $country, string $customerType): void
    {
        $plansQuery = new InstallmentPlansQuery($amount, $currency, $country, $customerType);
        $plans = (new PaylaterInstallmentPlans())->setQueryParameter($plansQuery);
        $this->assertEquals("plans?amount={$amount}&country={$country}&currency={$currency}&customerType={$customerType}", $plans->getResourcePath());
    }

    /**
     * Verify that Jsonresponse gets handled properly
     *
     * @test
     */
    public function verifyJsonResponseIsHandledProperly(): void
    {
        // when
        $paylaterInstallmentPlans = new PaylaterInstallmentPlans();
        $jsonResponse = json_decode(JsonProvider::getJsonFromFile('paylaterPlansResponse.json'));
        $paylaterInstallmentPlans->handleResponse($jsonResponse);

        $this->assertCount(count($jsonResponse->plans), $paylaterInstallmentPlans->getPlans());

        // then
        $this->assertEquals($jsonResponse->id ?? null, $paylaterInstallmentPlans->getId());
        $this->assertEquals($jsonResponse->amount ?? null, $paylaterInstallmentPlans->getAmount());
        $this->assertEquals($jsonResponse->currency ?? null, $paylaterInstallmentPlans->getCurrency());
        $this->assertEquals($jsonResponse->isError ?? null, $paylaterInstallmentPlans->isError());
        $this->assertEquals($jsonResponse->isSuccess ?? null, $paylaterInstallmentPlans->isSuccess());
        $this->assertEquals($jsonResponse->isPending ?? null, $paylaterInstallmentPlans->isPending());

        $this->comparePlans($jsonResponse->plans, $paylaterInstallmentPlans->getPlans());
    }

    //<editor-fold desc="Data Providers">

    /**
     * @param object[]          $expected
     * @param InstallmentPlan[] $actual
     *
     * @return void
     */
    protected function comparePlans(array $expected, array $actual): void
    {
        $this->assertCount(count($expected), $actual);

        foreach ($expected as $index => $plan) {
            $this->assertEquals($plan->totalAmount ?? null, $actual[$index]->getTotalAmount());
            $this->assertEquals($plan->nominalInterestRate ?? null, $actual[$index]->getNominalInterestRate());
            $this->assertEquals($plan->effectiveInterestRate ?? null, $actual[$index]->getEffectiveInterestRate());
            $this->compareRates($plan->installmentRates ?? null, $actual[$index]->getInstallmentRates());
            $this->assertEquals($plan->secciUrl ?? null, $actual[$index]->getSecciUrl());
        }
    }

    //</editor-fold>

    //<editor-fold desc="Helper">

    /**
     * @param object[]          $expected
     * @param InstallmentRate[] $actual
     *
     * @return void
     */
    protected function compareRates(array $expected, array $actual): void
    {
        foreach ($expected as $index => $rate) {
            $this->assertEquals($rate->date, $actual[$index]->getDate());
            $this->assertEquals($rate->rate, $actual[$index]->getRate());
        }
    }

    /**
     * @return array
     */
    public function verifyQueryStringDP(): array
    {
        return [
            [100, 'EUR', 'DE', 'B2C'],
            [100, 'EUR', 'GB', 'B2B'],
            [100, 'CHF', 'CHE', 'B2C'],
        ];
    }
    //</editor-fold>
}
