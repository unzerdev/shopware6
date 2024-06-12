<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the resource name service.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Constants;

use UnzerSDK\Constants\PaymentState;
use UnzerSDK\test\BasePaymentTest;
use RuntimeException;

class PaymentStateTest extends BasePaymentTest
{
    /**
     * This should verify the mapping of the payment state to the state code.
     *
     * @test
     *
     * @dataProvider codeToNameDataProvider
     *
     * @param integer $code
     * @param string  $name
     */
    public function shouldMapCodeToName($code, $name): void
    {
        $this->assertEquals($name, PaymentState::mapStateCodeToName($code));
    }

    /**
     * This should verify the mapping of the payment state to the state code.
     *
     * @test
     *
     * @dataProvider nameToCodeDataProvider
     *
     * @param integer $code
     * @param string  $name
     */
    public function shouldMapNameToCode($name, $code): void
    {
        $this->assertEquals($code, PaymentState::mapStateNameToCode($name));
    }

    /**
     * This verifies that an exception is thrown when the code to map is unknown.
     *
     * @test
     */
    public function mapCodeToNameShouldThrowAnExceptionIfTheCodeIsUnknown(): void
    {
        $this->expectException(RuntimeException::class);

        PaymentState::mapStateCodeToName(7);
    }

    /**
     * This verifies that an exception is thrown when the name to map is unknown.
     *
     * @test
     */
    public function mapNameToCodeShouldThrowAnExceptionIfTheNameIsUnknown(): void
    {
        $this->expectException(RuntimeException::class);

        PaymentState::mapStateNameToCode('unknown');
    }

    //<editor-fold desc="Data Providers">

    /**
     * Provides data to test code to name mapper.
     *
     * @return array
     */
    public function codeToNameDataProvider(): array
    {
        return[
            [PaymentState::STATE_PENDING, 'pending'],
            [PaymentState::STATE_COMPLETED, 'completed'],
            [PaymentState::STATE_CANCELED, 'canceled'],
            [PaymentState::STATE_PARTLY, 'partly'],
            [PaymentState::STATE_PAYMENT_REVIEW, 'payment review'],
            [PaymentState::STATE_CHARGEBACK, 'chargeback']
        ];
    }

    /**
     * Provides data to test name to code mapper.
     *
     * @return array
     */
    public function nameToCodeDataProvider(): array
    {
        return[
            [PaymentState::STATE_NAME_PENDING, 0],
            [PaymentState::STATE_NAME_COMPLETED, 1],
            [PaymentState::STATE_NAME_CANCELED, 2],
            [PaymentState::STATE_NAME_PARTLY, 3],
            [PaymentState::STATE_NAME_PAYMENT_REVIEW, 4],
            [PaymentState::STATE_NAME_CHARGEBACK, 5]
        ];
    }

    //</editor-fold>
}
