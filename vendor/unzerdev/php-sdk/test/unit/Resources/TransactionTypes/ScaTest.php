<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the SCA transaction type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\TransactionTypes;

use UnzerSDK\Resources\TransactionTypes\Sca;
use UnzerSDK\test\BasePaymentTest;

class ScaTest extends BasePaymentTest
{
    /**
     * @test
     */
    public function constructorTest()
    {
        $sca = new Sca(99.99, "EUR", "https://return-url.com");
        $this->assertEquals(99.99, $sca->getAmount());
        $this->assertEquals("EUR", $sca->getCurrency());
        $this->assertEquals("https://return-url.com", $sca->getReturnUrl());
    }

    /**
     * @test
     */
    public function getterSetterTest()
    {
        $sca = new Sca(99.99, "EUR", "https://return-url.com");
        $sca->setPaymentReference('reference');
        $this->assertEquals(99.99, $sca->getAmount());
        $this->assertEquals("EUR", $sca->getCurrency());
        $this->assertEquals("reference", $sca->getPaymentReference());
        $this->assertEquals("https://return-url.com", $sca->getReturnUrl());
    }
}
