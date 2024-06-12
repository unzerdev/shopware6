<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify metadata functionalities.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\test\BaseIntegrationTest;

class SetMetadataTest extends BaseIntegrationTest
{
    /**
     * Verify Metadata can be created and fetched with the API.
     *
     * @test
     */
    public function metadataShouldBeCreatableAndFetchableWithTheApi(): void
    {
        $metadata = new Metadata();
        $this->assertNull($metadata->getShopType());
        $this->assertNull($metadata->getShopVersion());
        $this->assertNull($metadata->getMetadata('MyCustomData'));

        $metadata->setShopType('my awesome shop');
        $metadata->setShopVersion('v2.0.0');
        $metadata->addMetadata('MyCustomData', 'my custom information');
        $this->assertNull($metadata->getId());

        $this->unzer->createMetadata($metadata);
        $this->assertNotNull($metadata->getId());

        $fetchedMetadata = (new Metadata())->setParentResource($this->unzer)->setId($metadata->getId());
        $this->assertNull($fetchedMetadata->getShopType());
        $this->assertNull($fetchedMetadata->getShopVersion());
        $this->assertNull($fetchedMetadata->getMetadata('MyCustomData'));

        $this->unzer->fetchMetadata($fetchedMetadata);
        $this->assertEquals('my awesome shop', $fetchedMetadata->getShopType());
        $this->assertEquals('v2.0.0', $fetchedMetadata->getShopVersion());
        $this->assertEquals('my custom information', $fetchedMetadata->getMetadata('MyCustomData'));
    }

    /**
     * Verify metadata will automatically created on authorize.
     *
     * @test
     */
    public function authorizeShouldCreateMetadata(): void
    {
        $metadata = new Metadata();
        $metadata->setShopType('Shopware');
        $metadata->setShopVersion('5.12');
        $metadata->addMetadata('ModuleType', 'Shopware 5');
        $metadata->addMetadata('ModuleVersion', '18.3.12');
        $this->assertEmpty($metadata->getId());

        $paypal = $this->unzer->createPaymentType(new Paypal());
        $this->unzer->authorize(1.23, 'EUR', $paypal, 'https://unzer.com', null, null, $metadata);
        $this->assertNotEmpty($metadata->getId());
    }

    /**
     * Verify metadata will automatically created on charge.
     *
     * @test
     */
    public function chargeShouldCreateMetadata(): void
    {
        $metadata = new Metadata();
        $metadata->setShopType('Shopware');
        $metadata->setShopVersion('5.12');
        $metadata->addMetadata('ModuleType', 'Shopware 5');
        $metadata->addMetadata('ModuleVersion', '18.3.12');
        $this->assertEmpty($metadata->getId());

        $paymentType = $this->unzer->createPaymentType(new Paypal());
        $this->unzer->charge(1.23, 'EUR', $paymentType, 'https://unzer.com', null, null, $metadata);
        $this->assertNotEmpty($metadata->getId());
    }

    /**
     * Verify Metadata is fetched when payment is fetched.
     *
     * @test
     */
    public function paymentShouldFetchMetadataResourceOnFetch(): void
    {
        $metadata = (new Metadata())->addMetadata('key', 'value');

        /** @var Paypal $paymentType */
        $paymentType = $this->unzer->createPaymentType(new Paypal());
        $authorize = $paymentType->authorize(10.0, 'EUR', 'https://unzer.com', null, null, $metadata);
        $payment = $authorize->getPayment();
        $this->assertSame($metadata, $payment->getMetadata());

        $fetchedPayment = $this->unzer->fetchPayment($payment->getId());
        $fetchedMetadata = $fetchedPayment->getMetadata();
        $this->assertEquals($metadata->expose(), $fetchedMetadata->expose());
    }

    /**
     * Verify error is thrown when metadata is empty.
     *
     * @test
     */
    public function emptyMetaDataShouldLeadToError(): void
    {
        $metadata = new Metadata();
        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_REQUEST_DATA_IS_INVALID);
        $this->unzer->createMetadata($metadata);
    }
}
