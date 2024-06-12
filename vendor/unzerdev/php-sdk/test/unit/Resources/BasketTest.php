<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the Basket resource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources;

use stdClass;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\Unzer;

class BasketTest extends BasePaymentTest
{
    /**
     * Verify getters and setters work properly.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkProperly(): void
    {
        $basket = new Basket();
        $this->assertEquals(0, $basket->getAmountTotalGross());
        $this->assertEquals(0, $basket->getAmountTotalDiscount());
        $this->assertEquals(0, $basket->getAmountTotalVat());
        $this->assertEquals(0, $basket->getTotalValueGross());
        $this->assertEquals('EUR', $basket->getCurrencyCode());
        $this->assertEquals('', $basket->getNote());
        $this->assertEquals('', $basket->getOrderId());
        $this->assertIsEmptyArray($basket->getBasketItems());
        $this->assertNull($basket->getBasketItemByIndex(1));

        $basket->setAmountTotalGross(12.34);
        $basket->setTotalValueGross(99.99);
        $basket->setAmountTotalDiscount(34.56);
        $basket->setAmountTotalVat(45.67);
        $basket->setCurrencyCode('USD');
        $basket->setNote('This is something I have to remember!');
        $basket->setOrderId('myOrderId');
        $this->assertEquals(12.34, $basket->getAmountTotalGross());
        $this->assertEquals(99.99, $basket->getTotalValueGross());
        $this->assertEquals(34.56, $basket->getAmountTotalDiscount());
        $this->assertEquals(45.67, $basket->getAmountTotalVat());
        $this->assertEquals('USD', $basket->getCurrencyCode());
        $this->assertEquals('This is something I have to remember!', $basket->getNote());
        $this->assertEquals('myOrderId', $basket->getOrderId());

        $this->assertEquals(0, $basket->getItemCount());
        $basketItem1 = new BasketItem();
        $basket->addBasketItem($basketItem1);
        $this->assertEquals(1, $basket->getItemCount());
        $this->assertSame($basketItem1, $basket->getBasketItemByIndex(0));

        $basketItem2 = new BasketItem();
        $basket->addBasketItem($basketItem2);
        $this->assertEquals(2, $basket->getItemCount());
        $this->assertNotSame($basketItem2, $basket->getBasketItemByIndex(0));
        $this->assertSame($basketItem2, $basket->getBasketItemByIndex(1));

        $this->assertEquals([$basketItem1, $basketItem2], $basket->getBasketItems());

        $basket->setBasketItems([]);
        $this->assertEquals(0, $basket->getItemCount());
        $this->assertIsEmptyArray($basket->getBasketItems());
        $this->assertNull($basket->getBasketItemByIndex(0));
        $this->assertNull($basket->getBasketItemByIndex(1));
    }

    /**
     * Verify expose will call expose on all attached BasketItems.
     *
     * @test
     */
    public function exposeShouldCallExposeOnAllAttachedBasketItems(): void
    {
        $basketItemMock = $this->getMockBuilder(BasketItem::class)->setMethods(['expose'])->getMock();
        $basketItemMock->expects($this->once())->method('expose')->willReturn('resultItem1');
        $basketItemMock2 = $this->getMockBuilder(BasketItem::class)->setMethods(['expose'])->getMock();
        $basketItemMock2->expects($this->once())->method('expose')->willReturn('resultItem2');

        $basket = (new Basket())->setBasketItems([$basketItemMock, $basketItemMock2]);

        $basketItemsExposed = $basket->expose()['basketItems'];
        $this->assertContains('resultItem1', $basketItemsExposed);
        $this->assertContains('resultItem2', $basketItemsExposed);
    }

    /**
     * Verify handleResponse will create basket items for each basketitem in response.
     *
     * @test
     */
    public function handleResponseShouldCreateBasketItemObjectsForAllBasketItemsInResponse(): void
    {
        $response                = new stdClass();
        $response->basketItems   = [];
        $basketItem1             = new stdClass();
        $basketItem2             = new stdClass();
        $response->basketItems[] = $basketItem1;
        $response->basketItems[] = $basketItem2;

        $basket =  new Basket();
        $this->assertEquals(0, $basket->getItemCount());
        $basket->handleResponse($response);
        $this->assertEquals(2, $basket->getItemCount());
        $basket->handleResponse($response);
        $this->assertEquals(2, $basket->getItemCount());
    }

    /**
     * Verify BasketItemReferenceId is set automatically to the items index within the basket array if it is not set.
     *
     * @test
     */
    public function referenceIdShouldBeAutomaticallySetToTheArrayIndexIfItIsNotSet(): void
    {
        $basketItem1 = new BasketItem();
        $this->assertNull($basketItem1->getBasketItemReferenceId());

        $basketItem2 = new BasketItem();
        $this->assertNull($basketItem2->getBasketItemReferenceId());

        $basket = new Basket();
        $basket->addBasketItem($basketItem1)->addBasketItem($basketItem2);
        $this->assertEquals('0', $basketItem1->getBasketItemReferenceId());
        $this->assertEquals('1', $basketItem2->getBasketItemReferenceId());

        $basketItem3 = new BasketItem();
        $this->assertNull($basketItem3->getBasketItemReferenceId());

        $basketItem4 = new BasketItem();
        $this->assertNull($basketItem4->getBasketItemReferenceId());

        $basket2 = new Basket('myOrderId', 123.0, 'EUR', [$basketItem3, $basketItem4]);
        $this->assertSame($basket2->getBasketItemByIndex(0), $basketItem3);
        $this->assertSame($basket2->getBasketItemByIndex(1), $basketItem4);
        $this->assertEquals('0', $basketItem3->getBasketItemReferenceId());
        $this->assertEquals('1', $basketItem4->getBasketItemReferenceId());
    }

    /**
     * Verify basket provides expected API version based ond set parameters.
     *
     * @test
     *
     * @dataProvider getApiVersionShouldReturnExpectedVersionDP
     *
     * @param Basket $basket
     * @param        $expectedApiVersion
     */
    public function getApiVersionShouldReturnExpectedVersion(Basket $basket, $expectedApiVersion): void
    {
        $this->assertEquals($expectedApiVersion, $basket->getApiVersion());
    }

    //<editor-fold desc="Data provider">

    /**
     * @return array
     */
    public function getApiVersionShouldReturnExpectedVersionDP(): array
    {
        $v1Basket = (new Basket())->setAmountTotalGross(100);
        $v2Basket = (new Basket())->setTotalValueGross(100);
        $mixedBasket = (new Basket())->setAmountTotalGross(100)->setTotalValueGross(100);
        return [
            'empty basket ' => [new Basket(), Unzer::API_VERSION],
            'minimum v1 basket ' => [$v1Basket, Unzer::API_VERSION],
            'minimum v2 basket ' => [$v2Basket, BasePaymentTest::API_VERSION_2],
            'mixed v1/v2 basket ' => [$mixedBasket, BasePaymentTest::API_VERSION_2],
        ];
    }

    //</editor-fold>
}
