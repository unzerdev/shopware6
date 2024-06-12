<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify backwards compatibility with deprecated hire-purchase-direct-debit resource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\PaymentTypes;

use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\test\Helper\TestEnvironmentService;

use function count;

class HirePurchaseTest extends BaseIntegrationTest
{
    protected function setUp(): void
    {
        $this->getUnzerObject(TestEnvironmentService::getLegacyTestPrivateKey());
    }

    /**
     * Verify, backwards compatibility regarding fetching payment type and map it to invoice secured class.
     *
     * @test
     */
    public function hddTypeShouldBeFetchable(): InstallmentSecured
    {
        // Mock a hdd Type
        $date = $this->getTodaysDateString();
        $requestData =
            [
                "iban" => "DE89370400440532013000",
                "bic" => "COBADEFFXXX",
                "accountHolder" => "Max Mustermann",
                "invoiceDueDate" => $date,
                "numberOfRates" => 3,
                "invoiceDate" => $date,
                "dayOfPurchase" => $date,
                "orderDate" => $date,
                "totalPurchaseAmount" => 119,
                "totalInterestAmount" => 0.96,
                "totalAmount" => 119.96,
                "effectiveInterestRate" => 4.99,
                "nominalInterestRate" => 4.92,
                "feeFirstRate" => 0,
                "feePerRate" => 0,
                "monthlyRate" => 39.99,
                "lastRate" => 39.98,
        ];

        $payload = json_encode($requestData);
        $hddMock = $this->getMockBuilder(InstallmentSecured::class)
            ->setMethods(['getUri', 'jsonSerialize'])
            ->getMock();
        $hddMock->method('getUri')->willReturn('/types/hire-purchase-direct-debit');
        $hddMock->method('jsonSerialize')->willReturn($payload);

        // When
        /** @var InstallmentSecured $hddMock */
        $this->unzer->createPaymentType($hddMock);
        $this->assertMatchesRegularExpression('/^s-hdd-[.]*/', $hddMock->getId());

        // Then
        $fetchedType = $this->unzer->fetchPaymentType($hddMock->getId());
        $this->assertInstanceOf(InstallmentSecured::class, $fetchedType);
        $this->assertMatchesRegularExpression('/^s-hdd-[.]*/', $fetchedType->getId());

        return $fetchedType;
    }

    /**
     * Verify fetched hdd type can be authorized and charged
     *
     * @test
     *
     * @depends hddTypeShouldBeFetchable
     *
     * @param InstallmentSecured $hddType fetched hdd type.
     *
     * @return AbstractUnzerResource|Charge
     *
     * @throws UnzerApiException
     */
    public function hddTypeAuthorizeAndCharge(InstallmentSecured $hddType)
    {
        $customer = $this->getMaximumCustomer();
        $basket = $this->createBasket();

        $auth = $hddType->authorize(119.00, 'EUR', 'https://unzer.com', $customer, null, null, $basket);
        $charge = $auth->getPayment()->charge();
        $this->assertNotNull($auth);
        $this->assertNotEmpty($auth->getId());
        $this->assertTrue($auth->isSuccess());

        return $charge;
    }

    /**
     * Verify fetched hdd payment can be shipped.
     *
     * @test
     *
     * @depends hddTypeAuthorizeAndCharge
     */
    public function hddTypeShouldBeShippable(Charge $hddCharge)
    {
        $invoiceId = 'i' . self::generateRandomId();
        $ship = $this->unzer->ship($hddCharge->getPayment(), $invoiceId);

        $this->assertNotNull($ship);
        return $hddCharge;
    }

    /**
     * Verify full cancel of charged HP after shipment.
     *
     * @test
     *
     * @depends hddTypeAuthorizeAndCharge
     */
    public function hddChargeCanBePartiallyCancledBeforeShipment(Charge $hddCharge): void
    {
        $payment = $hddCharge->getPayment();

        $cancel1 = $payment->cancelAmount(66, null, null, 60, 6);
        $cancel2 = $payment->cancelAmount(43, null, null, 40, 3);
        $this->assertGreaterThan(0, count($cancel1));
        $this->assertGreaterThan(0, count($cancel2));
    }
}
