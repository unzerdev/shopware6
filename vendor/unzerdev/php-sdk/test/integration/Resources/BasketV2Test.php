<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify Basket functionalities.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration\Resources;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;
use UnzerSDK\test\BaseIntegrationTest;

class BasketV2Test extends BaseIntegrationTest
{
    /**
     * Verify basket can be created and fetched.
     *
     * @test
     */
    public function minV2BasketShouldBeCreatableAndFetchable(): void
    {
        $basket = new Basket();
        $basket->setTotalValueGross(100)
            ->setCurrencyCode('EUR');
        $basketItem = new BasketItem();
        $basketItem->setBasketItemReferenceId('item1')
            ->setQuantity(1)
            ->setAmountPerUnitGross(100)
            ->setTitle('title');
        $basket->addBasketItem($basketItem);
        $this->assertEmpty($basket->getId());

        $this->unzer->createBasket($basket);
        $this->assertNotEmpty($basket->getId());

        $fetchedBasket = $this->unzer->fetchBasket($basket->getId())->setOrderId('');
        $this->assertEquals($basket->expose(), $fetchedBasket->expose());
    }

    /**
     * Verify basket can be created and fetched.
     *
     * @test
     */
    public function BasketShouldBeCreatableAndFetchable(): void
    {
        $orderId = microtime(true);
        $basket = new Basket();
        $basket->setTotalValueGross(100)
            ->setOrderId('testOrderId')
            ->setCurrencyCode('EUR');
        $basketItem = new BasketItem();
        $basketItem->setBasketItemReferenceId('item1')
            ->setQuantity(1)
            ->setAmountPerUnitGross(100)
            ->setTitle('title');
        $basket->addBasketItem($basketItem);
        $this->assertEmpty($basket->getId());

        $this->unzer->createBasket($basket);
        $this->assertNotEmpty($basket->getId());

        $fetchedBasket = $this->unzer->fetchBasket($basket->getId());
        $this->assertEquals($basket->expose(), $fetchedBasket->expose());
    }

    /**
     * Verify basket can be created and fetched.
     *
     * @test
     */
    public function maxBasketShouldBeCreatableAndFetchable(): void
    {
        $orderId = 'o'. self::generateRandomId();
        $basket = new Basket();
        $basket->setOrderId($orderId)
            ->setCurrencyCode('EUR')
            ->setNote('note this!')
            ->setTotalValueGross(133.33);

        $basketItem = (new BasketItem())
            ->setBasketItemReferenceId('refIdOne')
            ->setQuantity(10)
            ->setAmountPerUnitGross(10.11)
            ->setVat(19)
            ->setTitle('Item Title 1')
            ->setUnit('ert')
            ->setAmountDiscountPerUnitGross(0.11)
            ->setSubTitle('This is some subtitle for this item')
            ->setImageUrl('https://docs.unzer.com/card/card.png')
            ->setType('this is some type');
        $basket->addBasketItem($basketItem);

        $basketItem = (new BasketItem())
            ->setBasketItemReferenceId('refIdtwo')
            ->setQuantity(1)
            ->setAmountPerUnitGross(33.33)
            ->setVat(19.5)
            ->setTitle('Item Title 1')
            ->setUnit('ert')
            ->setAmountDiscountPerUnitGross(0.0)
            ->setSubTitle('This is some subtitle for this item')
            ->setImageUrl('https://docs.unzer.com/card/card.png')
            ->setType('this is some type');
        $basket->addBasketItem($basketItem);

        $this->assertEmpty($basket->getId());

        $this->unzer->createBasket($basket);
        $this->assertNotEmpty($basket->getId());

        $fetchedBasket = $this->unzer->fetchBasket($basket->getId());
        $exposedBasket = $basket->expose();
        unset($exposedBasket['note']);
        $this->assertEquals($exposedBasket, $fetchedBasket->expose());
        $this->assertEquals(
            $basket->getBasketItemByIndex(0)->expose(),
            $fetchedBasket->getBasketItemByIndex(0)->expose()
        );
    }

    /**
     * Verify basketItem with invalid imageUrl will return an error.
     *
     * @test
     *
     * @dataProvider basketItemWithInvalidUrlWillThrowAnErrorDP
     *
     * @param      $expectException
     * @param      $imageUrl
     * @param null $exceptionCode
     */
    public function basketItemWithInvalidUrlWillThrowAnError($expectException, $imageUrl, $exceptionCode = null): void
    {
        $basket = new Basket('b' . self::generateRandomId());
        $basket->setTotalValueGross(100)
            ->setCurrencyCode('EUR');

        $basketItem = (new BasketItem())
            ->setImageUrl($imageUrl)
            ->setAmountPerUnitGross(100)
            ->setQuantity(1)
            ->setBasketItemReferenceId('item1')
            ->setTitle('title');
        $basket->addBasketItem($basketItem);

        try {
            $this->unzer->createBasket($basket);
            $this->assertFalse(
                $expectException,
                'Failed asserting that exception of type "UnzerSDK\Exceptions\UnzerApiException" is thrown.'
            );
        } catch (UnzerApiException $e) {
            $this->assertTrue($expectException);
            $this->assertEquals($exceptionCode, $e->getCode());
            $this->assertNotNull($e->getErrorId());
        }
    }

    /**
     * Verify the Basket can be updated.
     *
     * @test
     */
    public function basketShouldBeUpdateable(): void
    {
        $orderId = 'b' . self::generateRandomId();
        $basket = new Basket($orderId);
        $basket->setTotalValueGross(99.99)
            ->setCurrencyCode('EUR');

        $basketItem = (new BasketItem())
            ->setAmountPerUnitGross(99.99)
            ->setQuantity(1)
            ->setBasketItemReferenceId('item1')
            ->setTitle('title');

        $basket->addBasketItem($basketItem);
        $this->unzer->createBasket($basket);

        $updateBasket = $this->unzer->fetchBasket($basket->getId());
        $updateBasket->setTotalValueGross(123.45)
            ->getBasketItemByIndex(0)
            ->setAmountPerUnitGross(123.45)
            ->setTitle('This item can also be updated!');
        $this->unzer->updateBasket($updateBasket);

        $this->unzer->fetchBasket($basket);
        $this->assertEquals($orderId, $basket->getOrderId());
        $this->assertEquals(123.45, $basket->getTotalValueGross());
        $this->assertNotEquals($basket->getBasketItemByIndex(0)->expose(), $basketItem->expose());
    }

    /**
     * Verify basket can be passed to the payment on authorize.
     *
     * @test
     */
    public function authorizeTransactionsShouldPassAlongTheBasketIdIfSet(): void
    {
        $basket = $this->createV2Basket();
        $this->assertNotEmpty($basket->getId());

        /** @var Paypal $paypal */
        $paypal = $this->unzer->createPaymentType(new Paypal());
        $authorize = $paypal->authorize(123.4, 'EUR', 'https://unzer.com', null, null, null, $basket);

        $fetchedPayment = $this->unzer->fetchPayment($authorize->getPaymentId());
        $this->assertEquals($basket->expose(), $fetchedPayment->getBasket()->expose());
    }

    /**
     * Verify basket can be passed to the payment on charge.
     *
     * @test
     */
    public function chargeTransactionsShouldPassAlongTheBasketIdIfSet(): void
    {
        $this->useLegacyKey();
        $basket  = $this->createV2Basket();
        $this->assertNotEmpty($basket->getId());

        $sdd = (new SepaDirectDebit('DE89370400440532013000'))->setBic('COBADEFFXXX');
        $this->unzer->createPaymentType($sdd);

        $customer = $this->getMaximumCustomerInclShippingAddress()->setShippingAddress($this->getBillingAddress());
        $charge   = $sdd->charge(119.0, 'EUR', self::RETURN_URL, $customer, null, null, $basket);

        $fetchedPayment = $this->unzer->fetchPayment($charge->getPaymentId());
        $this->assertEquals($basket->expose(), $fetchedPayment->getBasket()->expose());
    }

    /**
     * Verify basket will be created and passed to the payment on authorize if it does not exist yet.
     *
     * @test
     */
    public function authorizeTransactionsShouldCreateBasketIfItDoesNotExistYet(): void
    {
        $orderId = 'b' . self::generateRandomId();
        $basket = new Basket($orderId);
        $basket->setTotalValueGross(99.99)
            ->setCurrencyCode('EUR');

        $basketItem = (new BasketItem())
            ->setAmountPerUnitGross(99.99)
            ->setQuantity(1)
            ->setBasketItemReferenceId('item1')
            ->setTitle('title');
        $basket->addBasketItem($basketItem);
        $this->assertEmpty($basket->getId());

        /** @var Paypal $paypal */
        $paypal = $this->unzer->createPaymentType(new Paypal());
        $authorize = $paypal->authorize(123.4, 'EUR', 'https://unzer.com', null, null, null, $basket);
        $this->assertNotEmpty($basket->getId());

        $fetchedPayment = $this->unzer->fetchPayment($authorize->getPaymentId());
        $this->assertEquals($basket->expose(), $fetchedPayment->getBasket()->expose());
    }

    /**
     * Verify basket will be created and passed to the payment on charge if it does not exist yet.
     *
     * @test
     */
    public function chargeTransactionsShouldCreateBasketIfItDoesNotExistYet(): void
    {
        $orderId = 'b' . self::generateRandomId();
        $basket = new Basket($orderId);
        $basket->setTotalValueGross(99.99)
            ->setCurrencyCode('EUR');

        $basketItem = (new BasketItem())
            ->setAmountPerUnitGross(99.99)
            ->setQuantity(1)
            ->setBasketItemReferenceId('item1')
            ->setTitle('title');
        $basket->addBasketItem($basketItem);
        $this->assertEmpty($basket->getId());

        /** @var Paypal $paypal */
        $paypal = $this->unzer->createPaymentType(new Paypal());
        $charge = $paypal->charge(123.4, 'EUR', 'https://unzer.com', null, null, null, $basket);
        $this->assertNotEmpty($basket->getId());

        $fetchedPayment = $this->unzer->fetchPayment($charge->getPaymentId());
        $this->assertEquals($basket->expose(), $fetchedPayment->getBasket()->expose());
    }

    /**
     * @return array
     */
    public function basketItemWithInvalidUrlWillThrowAnErrorDP(): array
    {
        return [
            'valid ' => [false, 'https://docs.unzer.com/card/card.png'],
            'valid null' => [false, null],
            'valid empty' => [false, ''],
            'invalid not available' => [true, 'https://files.readme.io/does-not-exist.jpg', ApiResponseCodes::API_ERROR_BASKET_ITEM_IMAGE_INVALID_URL]
        ];
    }
}
