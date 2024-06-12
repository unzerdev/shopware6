<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the Keypair resource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources;

use UnzerSDK\Resources\Keypair;
use UnzerSDK\test\BasePaymentTest;

class KeypairTest extends BasePaymentTest
{
    /**
     * Verify getters and setters work properly.
     *
     * @test
     */
    public function gettersAndSettersWorkAsExpected(): void
    {
        $keypair = new Keypair();
        $this->assertFalse($keypair->isDetailed());
        $this->assertNull($keypair->getPublicKey());
        $this->assertNull($keypair->getPrivateKey());
        $this->assertEmpty($keypair->getPaymentTypes());
        $this->assertSame($keypair->getPaymentTypes(), $keypair->getAvailablePaymentTypes());
        $this->assertNull($keypair->isCof());
        $this->assertEquals('', $keypair->getSecureLevel());
        $this->assertEquals('', $keypair->getMerchantName());
        $this->assertEquals('', $keypair->getMerchantAddress());
        $this->assertEquals('', $keypair->getAlias());
        $this->assertFalse($keypair->isDetailed());
        $this->assertNull($keypair->isValidateBasket());

        $keypair->setDetailed(true);

        $this->assertTrue($keypair->isDetailed());
    }

    /**
     * Verify that a key pair can be updated on handle response.
     *
     * @test
     */
    public function aKeypairShouldBeUpdatedThroughResponseHandling(): void
    {
        // when
        $keypair = new Keypair();
        $paymentTypes = ['przelewy24', 'ideal', 'paypal', 'prepayment', 'invoice', 'sepa-direct-debit-secured', 'card', 'sofort', 'invoice-secured', 'sepa-direct-debit', 'giropay'];
        $testResponse = (object)[
            'publicKey'             => 's-pub-1234',
            'privateKey'            => 's-priv-4321',
            'availablePaymentTypes' => $paymentTypes,
            'cof'                   => true,
            'validateBasket'        => false
        ];
        $keypair->handleResponse($testResponse);

        // then
        $this->assertArrayContains($paymentTypes, $keypair->getPaymentTypes());
        $this->assertEquals('s-pub-1234', $keypair->getPublicKey());
        $this->assertEquals('s-priv-4321', $keypair->getPrivateKey());
        $this->assertTrue($keypair->isCof());
        $this->assertFalse($keypair->isValidateBasket());

        // when
        $testResponse = (object)['cof' => false, 'validateBasket' => true];
        $keypair->handleResponse($testResponse);
        $this->assertFalse($keypair->isCof());
        $this->assertTrue($keypair->isValidateBasket());
    }

    /**
     * Verify that a key pair can be updated with details on handle response.
     *
     * @test
     */
    public function aKeypairShouldBeUpdatedWithDetailsThroughResponseHandling(): void
    {
        $keypair = new Keypair();

        $paymentTypes = [
            (object) [
                'supports' => [
                    (object) [
                        'brands' => ['JCB', 'VISAELECTRON', 'MAESTRO', 'VISA', 'MASTER'],
                        'countries' => [],
                        'channel' => '31HA07BC819430D3495C56BC18C55622',
                        'currency' => ['CHF', 'CNY', 'JPY', 'USD', 'GBP', 'EUR']
                    ]
                ],
                'type' => 'card',
                'allowCustomerTypes' => 'B2C',
                'allowCreditTransaction' => true,
                '3ds' => true
            ],
            (object) [
                'supports' => [
                    (object) [
                        'brands' => ['CUP', 'SOLO', 'CARTEBLEUE', 'VISAELECTRON', 'MAESTRO', 'AMEX', 'VISA', 'MASTER'],
                        'countries' => [],
                        'channel' => '31HA07BC819430D3495C7C9D07B1A922',
                        'currency' => ['MGA', 'USD', 'GBP', 'EUR']
                    ]
                ],
                'type' => 'card',
                'allowCustomerTypes' => 'B2C',
                'allowCreditTransaction' => true,
                '3ds' => false
            ]
        ];

        $testResponse = (object) [
            'publicKey' => 's-pub-1234',
            'privateKey' => 's-priv-4321',
            'secureLevel' => 'SAQ-D',
            'alias' => 'Readme.io user',
            'merchantName' => 'Unzer GmbH',
            'merchantAddress' => 'Vangerowstraße 18, 69115 Heidelberg',
            'paymentTypes' => $paymentTypes
            ];

        $keypair->handleResponse($testResponse);
        $this->assertEquals($paymentTypes, $keypair->getPaymentTypes());
        $this->assertSame($keypair->getAvailablePaymentTypes(), $keypair->getPaymentTypes());
        $this->assertEquals('s-pub-1234', $keypair->getPublicKey());
        $this->assertEquals('s-priv-4321', $keypair->getPrivateKey());
        $this->assertEquals('SAQ-D', $keypair->getSecureLevel());
        $this->assertEquals('Readme.io user', $keypair->getAlias());
        $this->assertEquals('Unzer GmbH', $keypair->getMerchantName());
        $this->assertEquals('Vangerowstraße 18, 69115 Heidelberg', $keypair->getMerchantAddress());
    }
}
