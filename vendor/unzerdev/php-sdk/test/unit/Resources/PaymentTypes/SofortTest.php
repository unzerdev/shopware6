<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */

namespace UnzerSDK\test\unit\Resources\PaymentTypes;

use UnzerSDK\Resources\PaymentTypes\Sofort;
use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\test\Fixtures\JsonProvider;

class SofortTest extends BasePaymentTest
{
    /**
     * Verify setter bank account data are mapped on sofort type.
     *
     * @test
     *
     */
    public function responseShouldMapBankAccountData(): void
    {
        $sofort = new Sofort();
        $this->assertNull($sofort->getId());

        $jsonResponse = JsonProvider::getJsonFromFile('sofortResponseWithIban.json');
        $sofort->handleResponse((object)json_decode($jsonResponse, false));

        $this->assertEquals('s-sft-123', $sofort->getId());
        $this->assertEquals('DE-IBAN', $sofort->getIban());
        $this->assertEquals('test-bin', $sofort->getBic());
        $this->assertEquals('holder', $sofort->getHolder());
    }
}
