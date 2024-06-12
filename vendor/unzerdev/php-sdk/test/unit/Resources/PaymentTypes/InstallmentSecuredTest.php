<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of Installment Secured payment type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use DateInterval;
use DateTime;
use UnzerSDK\Resources\InstalmentPlan;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\test\BasePaymentTest;
use PHPUnit\Framework\MockObject\MockObject;

class InstallmentSecuredTest extends BasePaymentTest
{
    /**
     * Verify setter and getter work.
     *
     * @test
     */
    public function getterAndSetterWorkAsExpected(): void
    {
        $ins = new InstallmentSecured();
        $this->assertEmpty($ins->getTransactionParams());
        $this->assertNull($ins->getAccountHolder());
        $this->assertNull($ins->getIban());
        $this->assertNull($ins->getBic());
        $this->assertNull($ins->getOrderDate());
        $this->assertNull($ins->getNumberOfRates());
        $this->assertNull($ins->getDayOfPurchase());
        $this->assertNull($ins->getTotalPurchaseAmount());
        $this->assertNull($ins->getTotalInterestAmount());
        $this->assertNull($ins->getTotalAmount());
        $this->assertNull($ins->getEffectiveInterestRate());
        $this->assertNull($ins->getNominalInterestRate());
        $this->assertNull($ins->getFeeFirstRate());
        $this->assertNull($ins->getFeePerRate());
        $this->assertNull($ins->getMonthlyRate());
        $this->assertNull($ins->getLastRate());
        $this->assertEmpty($ins->getInvoiceDate());
        $this->assertEmpty($ins->getInvoiceDueDate());

        $ins->setAccountHolder(null)
            ->setIban(null)
            ->setBic(null)
            ->setOrderDate(null)
            ->setNumberOfRates(null)
            ->setDayOfPurchase(null)
            ->setTotalPurchaseAmount(null)
            ->setTotalInterestAmount(null)
            ->setTotalAmount(null)
            ->setEffectiveInterestRate(null)
            ->setNominalInterestRate(null)
            ->setFeeFirstRate(null)
            ->setFeePerRate(null)
            ->setMonthlyRate(null)
            ->setLastRate(null)
            ->setInvoiceDate(null)
            ->setInvoiceDueDate(null);

        $this->assertEmpty($ins->getTransactionParams());
        $this->assertNull($ins->getAccountHolder());
        $this->assertNull($ins->getIban());
        $this->assertNull($ins->getBic());
        $this->assertNull($ins->getOrderDate());
        $this->assertNull($ins->getNumberOfRates());
        $this->assertNull($ins->getDayOfPurchase());
        $this->assertNull($ins->getTotalPurchaseAmount());
        $this->assertNull($ins->getTotalInterestAmount());
        $this->assertNull($ins->getTotalAmount());
        $this->assertNull($ins->getEffectiveInterestRate());
        $this->assertNull($ins->getNominalInterestRate());
        $this->assertNull($ins->getFeeFirstRate());
        $this->assertNull($ins->getFeePerRate());
        $this->assertNull($ins->getMonthlyRate());
        $this->assertNull($ins->getLastRate());
        $this->assertEmpty($ins->getInvoiceDate());
        $this->assertEmpty($ins->getInvoiceDueDate());

        $ins->setAccountHolder('My Name')
            ->setIban('my IBAN')
            ->setBic('my BIC')
            ->setOrderDate($this->getYesterdaysTimestamp()->format('Y-m-d'))
            ->setNumberOfRates(15)
            ->setDayOfPurchase($this->getTodaysDateString())
            ->setTotalPurchaseAmount(119.0)
            ->setTotalInterestAmount(0.96)
            ->setTotalAmount(119.96)
            ->setEffectiveInterestRate(4.99)
            ->setNominalInterestRate(4.92)
            ->setFeeFirstRate(0)
            ->setFeePerRate(0)
            ->setMonthlyRate(39.99)
            ->setLastRate(39.98)
            ->setInvoiceDate($this->getTomorrowsTimestamp()->format('Y-m-d'))
            ->setInvoiceDueDate($this->getNextYearsTimestamp()->format('Y-m-d'));

        $this->assertEquals('My Name', $ins->getAccountHolder());
        $this->assertEquals('my IBAN', $ins->getIban());
        $this->assertEquals('my BIC', $ins->getBic());
        $this->assertEquals($this->getYesterdaysTimestamp()->format('Y-m-d'), $ins->getOrderDate());
        $this->assertEquals(15, $ins->getNumberOfRates());
        $this->assertEquals($this->getTodaysDateString(), $ins->getDayOfPurchase());
        $this->assertEquals(119.0, $ins->getTotalPurchaseAmount());
        $this->assertEquals(0.96, $ins->getTotalInterestAmount());
        $this->assertEquals(119.96, $ins->getTotalAmount());
        $this->assertEquals(4.99, $ins->getEffectiveInterestRate());
        $this->assertEquals(4.92, $ins->getNominalInterestRate());
        $this->assertEquals(0, $ins->getFeeFirstRate());
        $this->assertEquals(0, $ins->getFeePerRate());
        $this->assertEquals(39.99, $ins->getMonthlyRate());
        $this->assertEquals(39.98, $ins->getLastRate());
        $this->assertEquals($this->getTomorrowsTimestamp()->format('Y-m-d'), $ins->getInvoiceDate());
        $this->assertEquals($this->getNextYearsTimestamp()->format('Y-m-d'), $ins->getInvoiceDueDate());
        $this->assertEquals(['effectiveInterestRate' => $ins->getEffectiveInterestRate()], $ins->getTransactionParams());

        // test dates with DateTime objects
        $today = new DateTime();
        $ins->setOrderDate($today->add(new DateInterval('P1D')))
            ->setDayOfPurchase($today->add(new DateInterval('P1D')))
            ->setInvoiceDate($today->add(new DateInterval('P1D')))
            ->setInvoiceDueDate($today->add(new DateInterval('P1D')));

        $today = new DateTime();
        $this->assertEquals($today->add(new DateInterval('P1D'))->format('Y-m-d'), $ins->getOrderDate());
        $this->assertEquals($today->add(new DateInterval('P1D'))->format('Y-m-d'), $ins->getDayOfPurchase());
        $this->assertEquals($today->add(new DateInterval('P1D'))->format('Y-m-d'), $ins->getInvoiceDate());
        $this->assertEquals($today->add(new DateInterval('P1D'))->format('Y-m-d'), $ins->getInvoiceDueDate());

        // test dates with null
        $ins->setOrderDate(null)
            ->setDayOfPurchase(null)
            ->setInvoiceDate(null)
            ->setInvoiceDueDate(null);

        $this->assertNull($ins->getOrderDate());
        $this->assertNull($ins->getDayOfPurchase());
        $this->assertNull($ins->getInvoiceDate());
        $this->assertNull($ins->getInvoiceDueDate());
    }

    /**
     * Verify handle response is called with the exposed data of the selected instalment plan.
     *
     * @test
     */
    public function selectedInstalmentPlanDataIsUsedToUpdateInstalmentPlanInformation(): void
    {
        /** @var InstallmentSecured|MockObject $Mock */
        $Mock = $this->getMockBuilder(InstallmentSecured::class)->setMethods(['handleResponse'])->getMock();

        /** @var InstalmentPlan|MockObject $instalmentPlanMock */
        $instalmentPlanMock = $this->getMockBuilder(InstalmentPlan::class)->setMethods(['expose'])->getMock();

        $exposedObject = (object)['data' => 'I am exposed'];

        $instalmentPlanMock->expects($this->once())->method('expose')->willReturn($exposedObject);
        /** @noinspection PhpParamsInspection */
        $Mock->expects($this->once())->method('handleResponse')->with($exposedObject);

        $Mock->selectInstalmentPlan($instalmentPlanMock);
    }

    /**
     * Verify instalment plan fetch can update instalment plan properties.
     *
     * @test
     */
    public function instalmentPlanPropertiesShouldBeUpdateable(): void
    {
        $plan = new InstalmentPlan();
        $this->assertEmpty($plan->getInstallmentRates());

        $rates = [
            (object)['title' => 'first Rate'],
            (object)['title' => 'second Rate'],
            (object)['title' => 'third Rate']
        ];
        $planData = (object)['installmentRates' => $rates];

        $plan->handleResponse($planData);
        $this->assertEquals($rates, $plan->getInstallmentRates());
    }
}
