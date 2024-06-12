<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and
 * functionality of the payment method Installment Secured.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Constants\CustomerTypes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\Address;
use UnzerSDK\Resources\EmbeddedResources\Paylater\InstallmentPlansQuery;
use UnzerSDK\Resources\EmbeddedResources\Paylater\InstallmentPlan;
use UnzerSDK\Resources\PaymentTypes\PaylaterInstallment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;

use function count;

class PaylaterInstallmentTest extends BaseIntegrationTest
{
    /**
     * Verify that paylater installment plans can be fetched.
     *
     * @test
     */
    public function installmentPlanShouldBeFetchable(): void
    {
        $paylaterInstallmentPlans = new InstallmentPlansQuery(99.99, 'EUR', 'DE', 'B2C');
        $plans = $this->unzer->fetchPaylaterInstallmentPlans($paylaterInstallmentPlans);

        $this->assertGreaterThan(0, count($plans->getPlans()));
        $this->assertTrue($plans->isSuccess());
    }

    /**
     * Verify that paylater installment type can be created and fetched.
     *
     * @test
     */
    public function typeCanBeCreatedAndFetched(): void
    {
        // when
        $paylaterInstallmentPlans = new InstallmentPlansQuery(99.99, 'EUR', 'DE', 'B2C');
        $plans = $this->unzer->fetchPaylaterInstallmentPlans($paylaterInstallmentPlans);

        /** @var InstallmentPlan $selectedPlan */
        $selectedPlan = $plans->getPlans()[1];
        $ins = new PaylaterInstallment($plans->getId(), $selectedPlan->getNumberOfRates(), 'DE89370400440532013000', 'DE', 'Peter Mustermann');

        // then
        $this->unzer->createPaymentType($ins);
        $this->assertNotEmpty($ins->getId());
        $fetchedIns = $this->unzer->fetchPaymentType($ins->getId());
        $this->assertNotEmpty($fetchedIns->getId());
    }

    /**
     * Verify that paylater installment plans can be fetched.
     *
     * @test
     */
    public function fetchingPlansUsesB2CCustomerAsDefaultIfEmpty(): void
    {
        // when
        $paylaterInstallmentPlans = new InstallmentPlansQuery(99.99, 'EUR', 'DE');

        // then
        $this->assertEquals(CustomerTypes::B2C, $paylaterInstallmentPlans->getCustomerType());
        $plans = $this->unzer->fetchPaylaterInstallmentPlans($paylaterInstallmentPlans);
        $this->assertTrue($plans->isSuccess());
    }

    /**
     * Verify Api error is handled as Exception.
     *
     * @test
     */
    public function invalidInputThrowsUnzerApiException(): void
    {
        $this->expectException(UnzerApiException::class);
        $paylaterInstallmentPlans = new InstallmentPlansQuery(-99.99, 'EUR', 'DE');

        $this->assertEquals(CustomerTypes::B2C, $paylaterInstallmentPlans->getCustomerType());
        $this->unzer->fetchPaylaterInstallmentPlans($paylaterInstallmentPlans);
    }

    /**
     * Verify Installment Secured authorization (positive and negative).
     *
     * @test
     *
     * @param $firstname
     * @param $lastname
     * @param $errorCode
     */
    public function paylaterInstallmentAuthorize(): Authorization
    {
        $authorize = $this->createAuthorizeTransaction();
        $this->assertNotEmpty($authorize->getId());
        $this->assertTrue($authorize->isSuccess());

        return $authorize;
    }

    //<editor-fold desc="Cancel">

    /**
     * Verify charge.
     *
     * @test
     *
     * @depends paylaterInstallmentAuthorize
     *
     * @param mixed $authorize
     */
    public function verifyChargingAnInitializedPaylaterInstallment($authorize): Charge
    {
        $payment = $authorize->getPayment();
        $charge = $this->getUnzerObject()->performChargeOnPayment($payment, new Charge());
        $this->assertNotNull($charge->getId());
        $this->assertTrue($charge->isSuccess());

        return $charge;
    }

    /**
     * Verify partial charge.
     *
     * @test
     */
    public function verifyPartiallyChargingAnInitializedPaylaterInstallment(): Charge
    {
        $authorize = $this->createAuthorizeTransaction();
        $payment = $authorize->getPayment();

        $charge = $this->getUnzerObject()->performChargeOnPayment($payment, new Charge(33.33));
        $this->assertNotNull($charge->getId());
        $this->assertTrue($charge->isSuccess());

        return $charge;
    }

    /**
     * Verify full cancel of charged HP.
     *
     * @test
     *
     * @depends verifyChargingAnInitializedPaylaterInstallment
     */
    public function verifyChargeAndFullCancelAnInitializedPaylaterInstallment(Charge $charge): void
    {
        $payment = $charge->getPayment();
        $cancel = $this->getUnzerObject()->cancelChargedPayment($payment);

        // then
        $this->assertTrue($cancel->isSuccess());
    }

    //</editor-fold>

    /**
     * Verify full cancel of charged HP.
     *
     * @test
     */
    public function verifyPartlyCancelChargedPaylaterInstallment(): void
    {
        $authorize = $this->createAuthorizeTransaction();
        $payment = $authorize->getPayment();
        $this->getUnzerObject()->performChargeOnPayment($payment, new Charge());

        // when
        $cancel = $this->getUnzerObject()->cancelChargedPayment($payment, new Cancellation(66.66));
        $this->assertEmpty($cancel->getFetchedAt());

        // then
        $this->assertTrue($cancel->isSuccess());
        $this->assertTrue($payment->isCompleted());

        $fetchedPayment = $this->getUnzerObject()->fetchPayment($authorize->getPaymentId());
        $fetchedCancel = $this->getUnzerObject()->fetchPaymentRefund($fetchedPayment->getId(), $cancel->getId());
        $this->assertNotEmpty($fetchedCancel->getFetchedAt());
    }

    /**
     * Verify full cancel of charged HP.
     *
     * @test
     */
    public function verifyFullCancelAuthorizedPaylaterInstallment(): void
    {
        $authorize = $this->createAuthorizeTransaction();
        $payment = $authorize->getPayment();

        // when
        $cancel = $this->getUnzerObject()->cancelAuthorizedPayment($payment);
        $this->assertEmpty($cancel->getFetchedAt());

        // then
        $this->assertTrue($cancel->isSuccess());
        $this->assertTrue($payment->isCanceled());

        $fetchedPayment = $this->getUnzerObject()->fetchPayment($authorize->getPaymentId());
        $fetchedCancel = $this->getUnzerObject()->fetchPaymentReversal($fetchedPayment->getId(), $cancel->getId());
        $this->assertNotEmpty($fetchedCancel->getFetchedAt());
    }
    //<editor-fold desc="Helper">

    /** Performs a basic authorize transaction for Paylater invoice with amount 99.99 to set up test for follow-up transactions.
     *
     * @return Authorization
     *
     * @throws UnzerApiException
     */
    protected function createAuthorizeTransaction(): Authorization
    {
        $paylaterInstallmentPlans = new InstallmentPlansQuery(99.99, 'EUR', 'DE', 'B2C');
        $plans = $this->unzer->fetchPaylaterInstallmentPlans($paylaterInstallmentPlans);

        $selectedPlan = $plans->getPlans()[0];
        $ins = new PaylaterInstallment($plans->getId(), $selectedPlan->getNumberOfRates(), 'DE89370400440532013000', 'DE', 'Peter Mustermann');
        $this->unzer->createPaymentType($ins);

        $customer = $this->getCustomer()->setFirstname('Peter')->setLastname('Mustermann');
        $basket = $this->createBasket();

        $authorization = new Authorization(99.99, 'EUR', self::RETURN_URL);
        return $this->getUnzerObject()->performAuthorization($authorization, $ins, $customer, null, $basket);
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        $customer = CustomerFactory::createCustomer('Manuel', 'Weißmann');
        $address = (new Address())
            ->setStreet('Hugo-Junckers-Straße 3')
            ->setState('DE-BO')
            ->setZip('60386')
            ->setCity('Frankfurt am Main')
            ->setCountry('DE');
        $customer
            ->setBillingAddress($address)
            ->setBirthDate('2000-12-12')
            ->setEmail('manuel-weissmann@unzer.com');

        return $customer;
    }
    //</editor-fold>
}
