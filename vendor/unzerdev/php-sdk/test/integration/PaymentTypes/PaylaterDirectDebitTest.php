<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and
 * functionality of the payment method Paylater Direct Debit.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace PaymentTypes;

use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\Address;
use UnzerSDK\Resources\PaymentTypes\PaylaterDirectDebit;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;

class PaylaterDirectDebitTest extends BaseIntegrationTest
{
    /**
     * Verify that Paylater Direct Debit type can be created and fetched.
     *
     * @test
     */
    public function typeCanBeCreatedAndFetched(): void
    {
        $pdd = new PaylaterDirectDebit('DE89370400440532013000', 'Max Mustermann');

        // then
        $this->unzer->createPaymentType($pdd);
        $this->assertNotEmpty($pdd->getId());
        $fetchedIns = $this->unzer->fetchPaymentType($pdd->getId());
        $this->assertNotEmpty($fetchedIns->getId());
    }

    /**
     * Verify Paylater Direct Debit authorization (positive and negative).
     *
     * @test
     *
     * @param $firstname
     * @param $lastname
     * @param $errorCode
     */
    public function PaylaterDirectDebitAuthorize(): Authorization
    {
        $authorize = $this->createAuthorizeTransaction();
        $this->assertNotEmpty($authorize->getId());
        $this->assertTrue($authorize->isSuccess());

        return $authorize;
    }

    /** Performs a basic authorize transaction for Paylater invoice with amount 99.99 to set up test for follow-up transactions.
     *
     * @return Authorization
     *
     * @throws UnzerApiException
     */
    protected function createAuthorizeTransaction(): Authorization
    {
        $pdd = new PaylaterDirectDebit('DE89370400440532013000', 'Peter Mustermann');

        $this->unzer->createPaymentType($pdd);
        $customer = $this->getCustomer()->setFirstname('Peter')->setLastname('Mustermann');
        $basket = $this->createBasket();

        $authorization = new Authorization(99.99, 'EUR', self::RETURN_URL);
        return $this->getUnzerObject()->performAuthorization($authorization, $pdd, $customer, null, $basket);
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

    /**
     * Verify charge.
     *
     * @test
     *
     * @depends PaylaterDirectDebitAuthorize
     *
     * @param mixed $authorize
     */
    public function verifyChargingAnInitializedPaylaterDirectDebit($authorize): Charge
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
    public function verifyPartiallyChargingAnInitializedPaylaterDirectDebit(): Charge
    {
        $authorize = $this->createAuthorizeTransaction();
        $payment = $authorize->getPayment();

        $charge = $this->getUnzerObject()->performChargeOnPayment($payment, new Charge(33.33));
        $this->assertNotNull($charge->getId());
        $this->assertTrue($charge->isSuccess());
        $this->unzer->fetchPaymentType($payment->getPaymentType()->getId());

        return $charge;
    }

    /**
     * Verify full cancel of charged HP.
     *
     * @test
     *
     * @depends verifyChargingAnInitializedPaylaterDirectDebit
     */
    public function verifyChargeAndFullCancelAnInitializedPaylaterDirectDebit(Charge $charge): void
    {
        $payment = $charge->getPayment();
        $cancel = $this->getUnzerObject()->cancelChargedPayment($payment);

        // then
        $this->assertTrue($cancel->isSuccess());
    }

    /**
     * Verify full cancel of charged HP.
     *
     * @test
     */
    public function verifyPartlyCancelChargedPaylaterDirectDebit(): void
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
    public function verifyFullCancelAuthorizedPaylaterDirectDebit(): void
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
}
