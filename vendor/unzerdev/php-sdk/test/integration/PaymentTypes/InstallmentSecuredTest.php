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

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\Address;
use UnzerSDK\Resources\InstalmentPlan;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\test\Helper\TestEnvironmentService;

use function count;

class InstallmentSecuredTest extends BaseIntegrationTest
{
    protected function setUp(): void
    {
        $this->getUnzerObject(TestEnvironmentService::getLegacyTestPrivateKey());
    }

    /**
     * Verify the following features:
     * 1. fetching instalment plans.
     * 2. selecting plan
     * 3. create ins resource
     * 4. fetch ins resource
     * 5 test update ins resource
     *
     * @test
     */
    public function instalmentPlanShouldBeSelectable(): void
    {
        $plans = $this->unzer->fetchInstallmentPlans(119.0, 'EUR', 4.99);
        $this->assertGreaterThan(0, count($plans->getPlans()));

        /** @var InstalmentPlan $selectedPlan */
        $selectedPlan = $plans->getPlans()[1];

        $ins = new InstallmentSecured($selectedPlan, 'DE46940594210000012345', 'Manuel Weißmann');
        $this->unzer->createPaymentType($ins);
        $this->assertArrayContains($selectedPlan->expose(), $ins->expose());

        $fetchedIns = $this->unzer->fetchPaymentType($ins->getId());
        $this->assertEquals($ins->expose(), $fetchedIns->expose());

        $ins->setIban('DE89370400440532013000')
            ->setBic('COBADEFFXXX')
            ->setAccountHolder('Peter Universum')
            ->setInvoiceDate($this->getYesterdaysTimestamp())
            ->setInvoiceDueDate($this->getTomorrowsTimestamp());
        $insClone = clone $ins;
        $this->unzer->updatePaymentType($ins);
        $this->assertEquals($insClone->expose(), $ins->expose());
    }

    /**
     * Verify Installment Secured authorization (positive and negative).
     *
     * @test
     *
     * @dataProvider CustomerRankingDataProvider
     *
     * @param $firstname
     * @param $lastname
     * @param $errorCode
     */
    public function installmentSecuredAuthorize($firstname, $lastname, $errorCode): void
    {
        $hpPlans = $this->unzer->fetchInstallmentPlans(119.0, 'EUR', 4.99);
        /** @var InstalmentPlan $selectedPlan */
        $selectedPlan = $hpPlans->getPlans()[0];
        $ins = new InstallmentSecured($selectedPlan, 'DE46940594210000012345', 'Manuel Weißmann');
        $this->unzer->createPaymentType($ins);

        $customer = $this->getCustomer()->setFirstname($firstname)->setLastname($lastname);
        $basket = $this->createBasket();

        try {
            $authorize = $ins->authorize(119.0, 'EUR', self::RETURN_URL, $customer, null, null, $basket);
            if ($errorCode !== null) {
                $this->assertTrue(false, 'Expected error for negative ranking test.');
            }
            $this->assertNotEmpty($authorize->getId());
        } catch (UnzerApiException $e) {
            if ($errorCode !== null) {
                $this->assertEquals($errorCode, $e->getCode());
            } else {
                $this->assertTrue(false, "No error expected for positive ranking test. ({$e->getCode()})");
            }
        }
    }

    /**
     * Verify fetching instalment plans.
     *
     * @test
     */
    public function instalmentPlanSelectionWithAllFieldsSet(): void
    {
        $yesterday = $this->getYesterdaysTimestamp();
        $plans = $this->unzer->fetchInstallmentPlans(119.0, 'EUR', 4.99, $yesterday);
        $this->assertGreaterThan(0, count($plans->getPlans()));

        /** @var InstalmentPlan $selectedPlan */
        $selectedPlan = $plans->getPlans()[0];
        $this->assertCount($selectedPlan->getNumberOfRates(), $selectedPlan->getInstallmentRates(), 'The number of rates should equal the actual rate count.');
        $ins = new InstallmentSecured($selectedPlan, 'DE46940594210000012345', 'Manuel Weißmann', $yesterday, 'COBADEFFXXX', $yesterday, $this->getTomorrowsTimestamp());
        $this->unzer->createPaymentType($ins);
        $this->assertArrayContains($selectedPlan->expose(), $ins->expose());
    }

    /**
     * Verify charge.
     *
     * @test
     */
    public function verifyChargingAnInitializedInstallmentSecured(): void
    {
        $yesterday = $this->getYesterdaysTimestamp();
        $plans = $this->unzer->fetchInstallmentPlans(119.0, 'EUR', 4.99, $yesterday);
        $this->assertGreaterThan(0, count($plans->getPlans()));

        /** @var InstalmentPlan $selectedPlan */
        $selectedPlan = $plans->getPlans()[0];
        $ins = new InstallmentSecured($selectedPlan, 'DE46940594210000012345', 'Manuel Weißmann', $yesterday, 'COBADEFFXXX', $yesterday, $this->getTomorrowsTimestamp());
        $this->unzer->createPaymentType($ins);

        $authorize = $ins->authorize(119.0, 'EUR', self::RETURN_URL, $this->getCustomer(), null, null, $basket = $this->createBasket());
        $payment = $authorize->getPayment();
        $charge = $payment->charge();
        $this->assertNotNull($charge->getId());
    }

    //<editor-fold desc="Shipment">

    /**
     * Verify charge and ship.
     *
     * @test
     */
    public function verifyShippingAChargedInstallmentSecured(): void
    {
        $yesterday = $this->getYesterdaysTimestamp();
        $plans = $this->unzer->fetchInstallmentPlans(119.0, 'EUR', 4.99, $yesterday);

        /** @var InstalmentPlan $selectedPlan */
        $selectedPlan = $plans->getPlans()[0];
        $ins = new InstallmentSecured($selectedPlan, 'DE89370400440532013000', 'Manuel Weißmann', $yesterday, 'COBADEFFXXX', $this->getTodaysDateString(), $this->getTomorrowsTimestamp());
        $this->unzer->createPaymentType($ins);

        $authorize = $ins->authorize(119.0, 'EUR', self::RETURN_URL, $this->getCustomer(), null, null, $this->createBasket());
        $payment = $authorize->getPayment();
        $payment->charge();
        $shipment = $payment->ship();
        $this->assertNotNull($shipment->getId());
    }

    //</editor-fold>

    //<editor-fold desc="Charge cancel">

    /**
     * Verify full cancel of charged HP.
     *
     * @test
     */
    public function verifyChargeAndFullCancelAnInitializedInstallmentSecured(): void
    {
        $yesterday = $this->getYesterdaysTimestamp();
        $plans = $this->unzer->fetchInstallmentPlans(119.0, 'EUR', 4.99, $yesterday);
        $this->assertGreaterThan(0, count($plans->getPlans()));

        /** @var InstalmentPlan $selectedPlan */
        $selectedPlan = $plans->getPlans()[0];
        $ins = new InstallmentSecured($selectedPlan, 'DE46940594210000012345', 'Manuel Weißmann', $yesterday, 'COBADEFFXXX', $yesterday, $this->getTomorrowsTimestamp());
        $this->unzer->createPaymentType($ins);

        $authorize = $ins->authorize(119.0, 'EUR', self::RETURN_URL, $this->getCustomer(), null, null, $basket = $this->createBasket());
        $payment = $authorize->getPayment();
        $payment->charge();
        $cancel = $payment->cancelAmount();
        $this->assertGreaterThan(0, count($cancel));
    }

    /**
     * Verify full cancel of charged HP.
     *
     * @test
     */
    public function verifyPartlyCancelChargedInstallmentSecured(): void
    {
        $yesterday = $this->getYesterdaysTimestamp();
        $plans = $this->unzer->fetchInstallmentPlans(119.0, 'EUR', 4.99, $yesterday);
        $this->assertGreaterThan(0, count($plans->getPlans()));

        /** @var InstalmentPlan $selectedPlan */
        $selectedPlan = $plans->getPlans()[0];
        $ins = new InstallmentSecured($selectedPlan, 'DE46940594210000012345', 'Manuel Weißmann', $yesterday, 'COBADEFFXXX', $yesterday, $this->getTomorrowsTimestamp());
        $this->unzer->createPaymentType($ins);

        $authorize = $ins->authorize(119.0, 'EUR', self::RETURN_URL, $this->getCustomer(), null, null, $basket = $this->createBasket());
        $payment = $authorize->getPayment();
        $payment->charge();
        $cancel = $payment->cancelAmount(59.5, null, null, 50.0, 9.5);
        $this->assertCount(1, $cancel);
        $this->assertTrue($payment->isCompleted());
    }

    /**
     * Verify full cancel of charged HP after shipment.
     *
     * @test
     */
    public function verifyChargeAndFullCancelAnInitializedInstallmentSecuredAfterShipment(): void
    {
        $yesterday = $this->getYesterdaysTimestamp();
        $plans = $this->unzer->fetchInstallmentPlans(119.0, 'EUR', 4.99, $yesterday);
        $this->assertGreaterThan(0, count($plans->getPlans()));

        /** @var InstalmentPlan $selectedPlan */
        $selectedPlan = $plans->getPlans()[0];
        $ins = new InstallmentSecured($selectedPlan, 'DE89370400440532013000', 'Manuel Weißmann', $yesterday, 'COBADEFFXXX', $this->getTodaysDateString(), $this->getTomorrowsTimestamp());
        $this->unzer->createPaymentType($ins);

        $authorize = $ins->authorize(119.0, 'EUR', self::RETURN_URL, $this->getCustomer(), null, null, $basket = $this->createBasket());
        $payment = $authorize->getPayment();

        $charge = $payment->charge();
        $invoiceId = 'i' . self::generateRandomId();
        $ship = $this->unzer->ship($charge->getPayment(), $invoiceId);
        $this->assertNotNull($ship);

        $cancel = $payment->cancelAmount();
        $this->assertGreaterThan(0, count($cancel));
    }

    /**
     * Verify full cancel of charged HP after shipment.
     *
     * @test
     */
    public function verifyPartlyCancelChargedInstallmentSecuredAfterShipment(): void
    {
        $yesterday = $this->getYesterdaysTimestamp();
        $plans = $this->unzer->fetchInstallmentPlans(119.0, 'EUR', 4.99, $yesterday);
        $this->assertGreaterThan(0, count($plans->getPlans()));

        /** @var InstalmentPlan $selectedPlan */
        $selectedPlan = $plans->getPlans()[0];
        $ins = new InstallmentSecured($selectedPlan, 'DE89370400440532013000', 'Manuel Weißmann', $yesterday, 'COBADEFFXXX', $this->getTodaysDateString(), $this->getTomorrowsTimestamp());
        $this->unzer->createPaymentType($ins);

        $authorize = $ins->authorize(119.0, 'EUR', self::RETURN_URL, $this->getCustomer(), null, null, $basket = $this->createBasket());
        $payment = $authorize->getPayment();

        $charge = $payment->charge();
        $invoiceId = 'i' . self::generateRandomId();
        $ship = $this->unzer->ship($charge->getPayment(), $invoiceId);
        $this->assertNotNull($ship);

        $cancel = $payment->cancelAmount(59.5, null, null, 50.0, 9.5);
        $this->assertCount(1, $cancel);
        $this->assertTrue($payment->isCompleted());
    }

    //</editor-fold>

    //<editor-fold desc="Helper">

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

    //<editor-fold desc="Data Providers">

    /**
     * @return array
     */
    public function CustomerRankingDataProvider(): array
    {
        return [
            'positive' => ['Manuel', 'Weißmann', null],
            'negative #1 - Payment guarantee' => ['Manuel', 'Zeißmann', ApiResponseCodes::SDM_ERROR_CURRENT_INSURANCE_EVENT],
            'positive #2 - Limit exceeded' => ['Manuel', 'Leißmann', ApiResponseCodes::SDM_ERROR_LIMIT_EXCEEDED],
            'positive #3 - Negative trait' => ['Imuel', 'Seißmann', ApiResponseCodes::SDM_ERROR_NEGATIVE_TRAIT_FOUND],
            'positive #4 - Negative increased risk' => ['Jamuel', 'Seißmann', ApiResponseCodes::SDM_ERROR_INCREASED_RISK]
        ];
    }

    //</editor-fold>
}
