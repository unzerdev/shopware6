<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify metadata functionalities.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources;

use UnzerSDK\Resources\Metadata;
use UnzerSDK\test\BasePaymentTest;
use stdClass;

class MetadataTest extends BasePaymentTest
{
    /**
     * Verify custom data can be set.
     *
     * @test
     */
    public function metaDataShouldAllowForCustomDataToBeSet(): void
    {
        $metaData = new Metadata();
        $metaData->addMetadata('myCustomData', 'Wow I can add custom information');
        $this->assertEquals('Wow I can add custom information', $metaData->getMetadata('myCustomData'));

        $this->assertNull($metaData->getMetadata('myCustomDataNo2'));
    }

    /**
     * Verify defined data can not be set.
     *
     * @test
     */
    public function metadataShouldNotAllowForMagicAccessToSdkAndShopData(): void
    {
        $metaData = new Metadata();
        $metaData->addMetadata('sdkType', 'sdkType');
        $metaData->addMetadata('sdkVersion', 'sdkVersion');
        $metaData->addMetadata('shopType', 'myShopType');
        $metaData->addMetadata('shopVersion', 'myShopVersion');

        $this->assertNotEquals('shopType', $metaData->getMetadata('shopType'));
        $this->assertNotEquals('shopVersion', $metaData->getMetadata('sdkVersion'));

        $this->assertNull($metaData->getShopType());
        $this->assertNull($metaData->getShopVersion());
    }

    /**
     * Verify expose contains all defined data.
     *
     * @test
     */
    public function exposeShouldGatherAllDefinedDataInTheAnArray(): void
    {
        $metaData = new Metadata();
        $metaDataArray = (array)$metaData->expose();
        $this->assertCount(0, $metaDataArray);

        $metaData->addMetadata('myData', 'This should be my Data');
        $metaData->addMetadata('additionalData', 'some information');
        $metaData->setShopType('my own shop');
        $metaData->setShopVersion('1.0.0.0');

        $metaDataArray = $metaData->expose();
        $this->assertCount(4, $metaDataArray);
        $this->assertEquals('my own shop', $metaDataArray['shopType']);
        $this->assertEquals('1.0.0.0', $metaDataArray['shopVersion']);
        $this->assertEquals('This should be my Data', $metaDataArray['myData']);
        $this->assertEquals('some information', $metaDataArray['additionalData']);
    }

    /**
     * Verify metadata can be updated.
     *
     * @test
     */
    public function handleResponseShouldUpdateMetadata(): void
    {
        $metaData = new Metadata();
        $metaData->addMetadata('myData', 'This should be my Data');
        $metaData->addMetadata('additionalData', 'some information');
        $metaData->setShopType('my own shop');
        $metaData->setShopVersion('1.0.0.0');

        $this->assertNull($metaData->getId());
        $this->assertEquals('my own shop', $metaData->getShopType());
        $this->assertEquals('1.0.0.0', $metaData->getShopVersion());
        $this->assertEquals('This should be my Data', $metaData->getMetadata('myData'));
        $this->assertEquals('some information', $metaData->getMetadata('additionalData'));
        $this->assertNull($metaData->getMetadata('extraData'));

        $response = new stdClass();
        $response->id = 'newId';
        $response->shopType = 'my new shop';
        $response->shopVersion = '1.0.0.1';
        $response->myData = 'This should be my new Data';
        $response->additionalData = 'some new information';
        $response->extraData = 'This is brand new information';

        $metaData->handleResponse($response);
        $this->assertEquals('newId', $metaData->getId());
        $this->assertEquals('my new shop', $metaData->getShopType());
        $this->assertEquals('1.0.0.1', $metaData->getShopVersion());
        $this->assertEquals('This should be my new Data', $metaData->getMetadata('myData'));
        $this->assertEquals('some new information', $metaData->getMetadata('additionalData'));
        $this->assertEquals('This is brand new information', $metaData->getMetadata('extraData'));
    }
}
