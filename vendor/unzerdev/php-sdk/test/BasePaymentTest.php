<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class is the base class for all tests of this SDK.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test;

use DateInterval;
use DateTime;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;
use UnzerSDK\Resources\Recurring;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\test\Fixtures\CustomerFixtureTrait;
use UnzerSDK\Unzer;

class BasePaymentTest extends TestCase
{
    use CustomerFixtureTrait;
    protected const RETURN_URL = 'https://dev.unzer.com';
    public const API_VERSION_2 = 'v2';

    /** @var Unzer $unzer */
    protected $unzer;

    protected static $debughandler;

    /**
     * @return TestDebugHandler
     */
    public static function getDebugHandler(): TestDebugHandler
    {
        if (!self::$debughandler instanceof TestDebugHandler) {
            self::$debughandler = new TestDebugHandler();
        }
        return self::$debughandler;
    }

    /**
     * Creates and returns an Unzer object if it does not exist yet.
     * Uses an invalid but well formed default key if no key is given.
     *
     * @param string $privateKey
     *
     * @return Unzer
     *
     * @throws RuntimeException
     */
    protected function getUnzerObject($privateKey = 's-priv-1234'): Unzer
    {
        if (!$this->unzer instanceof Unzer) {
            $this->unzer = (new Unzer($privateKey))
                ->setDebugHandler(self::getDebugHandler())
                ->setDebugMode(true);
        }
        return $this->unzer;
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->getUnzerObject();
    }

    //<editor-fold desc="Custom asserts">

    /**
     * This performs assertions to verify the tested value is an empty array.
     *
     * @param mixed $value
     */
    public function assertIsEmptyArray($value): void
    {
        $this->assertIsArray($value);
        $this->assertEmpty($value);
    }

    /**
     * @param Payment $payment
     * @param float   $expectedRemaining
     * @param float   $expectedCharged
     * @param float   $expectedTotal
     * @param float   $expectedCanceled
     */
    protected function assertAmounts(
        $payment,
        $expectedRemaining,
        $expectedCharged,
        $expectedTotal,
        $expectedCanceled
    ): void {
        $amount = $payment->getAmount();
        $this->assertEquals($expectedRemaining, $amount->getRemaining(), 'The remaining amount does not match.');
        $this->assertEquals($expectedCharged, $amount->getCharged(), 'The charged amount does not match.');
        $this->assertEquals($expectedTotal, $amount->getTotal(), 'The total amount does not match.');
        $this->assertEquals($expectedCanceled, $amount->getCanceled(), 'The canceled amount does not match.');
    }

    /**
     * @param mixed $transactionType
     */
    public function assertTransactionResourceHasBeenCreated($transactionType): void
    {
        $this->assertNotNull($transactionType);
        $this->assertNotEmpty($transactionType->getId());
        $this->assertNotEmpty($transactionType->getUniqueId());
        $this->assertNotEmpty($transactionType->getShortId());
    }

    /**
     * Asserts whether the given transaction was successful.
     *
     * @param AbstractTransactionType|Recurring $transaction
     */
    protected function assertSuccess($transaction): void
    {
        $this->assertTrue($transaction->isSuccess());
        $this->assertFalse($transaction->isPending());
        $this->assertFalse($transaction->isError());
    }

    /**
     * Asserts whether the given transaction was a failure.
     *
     * @param AbstractTransactionType|Recurring $transaction
     */
    protected function assertError($transaction): void
    {
        $this->assertFalse($transaction->isSuccess());
        $this->assertFalse($transaction->isPending());
        $this->assertTrue($transaction->isError());
    }

    /**
     * Asserts whether the given transaction is pending.
     *
     * @param AbstractTransactionType|Recurring $transaction
     */
    protected function assertPending($transaction): void
    {
        $this->assertFalse($transaction->isSuccess());
        $this->assertTrue($transaction->isPending());
        $this->assertFalse($transaction->isError());
    }

    //</editor-fold>

    //<editor-fold desc="Helpers">

    /**
     * Creates a Basket resource and returns it.
     *
     * @return Basket
     */
    public function createBasket(): Basket
    {
        $orderId = 'b' . self::generateRandomId();
        $basket = new Basket($orderId, 119.0, 'EUR');
        $basket->setAmountTotalVat(19.0);
        $basket->setNote('This basket is creatable!');
        $basketItem = (new BasketItem('myItem', 100.0, 100.0, 1))
            ->setBasketItemReferenceId('refId')
            ->setAmountVat(19.0)
            ->setAmountGross(119.0)
            ->setImageUrl('https://hpp-images.s3.amazonaws.com/7/bsk_0_6377B5798E5C55C6BF8B5BECA59529130226E580B050B913EAC3606DA0FF4F68.jpg');
        $basket->addBasketItem($basketItem);
        $this->unzer->createBasket($basket);
        return $basket;
    }

    /**
     * Creates a v2 Basket resource and returns it.
     *
     * @return Basket
     */
    public function createV2Basket(): Basket
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
        return $basket;
    }

    /**
     * Creates a Card object for tests.
     *
     * @param string $cardNumber
     *
     * @return Card
     */
    protected function createCardObject(string $cardNumber = '4711100000000000'): Card
    {
        $expiryDate = $this->getNextYearsTimestamp()->format('m/Y');
        $card = new Card($cardNumber, $expiryDate);
        $card->setCvc('123')->setCardHolder('max mustermann');
        return $card;
    }

    /**
     * Creates and returns an Authorization object with the API which can be used in test methods.
     *
     * @param float $amount
     *
     * @return Authorization
     */
    public function createCardAuthorization($amount = 100.0): Authorization
    {
        $card    = $this->unzer->createPaymentType($this->createCardObject());
        $orderId = microtime(true);
        return $this->unzer->authorize($amount, 'EUR', $card, self::RETURN_URL, null, $orderId, null, null, false);
    }

    /**
     * Creates and returns an Authorization object with the API which can be used in test methods.
     *
     * @return Authorization
     */
    public function createPaypalAuthorization(): Authorization
    {
        /** @var Paypal $paypal */
        $paypal  = $this->unzer->createPaymentType(new Paypal());
        $orderId = microtime(true);
        return $this->unzer->authorize(100.0, 'EUR', $paypal, self::RETURN_URL, null, $orderId, null, null, false);
    }

    /**
     * Creates and returns a Charge object with the API which can be used in test methods.
     *
     * @param float $amount
     *
     * @return Charge
     */
    public function createCharge($amount = 100.0): Charge
    {
        $card = $this->unzer->createPaymentType(new SepaDirectDebit('DE89370400440532013000'));
        return $this->unzer->charge($amount, 'EUR', $card, self::RETURN_URL);
    }

    /**
     * Creates and returns an order id.
     *
     * @return string
     */
    public static function generateRandomId(): string
    {
        return str_replace('.', '', microtime(true));
    }

    /**
     * Returns the current date as string in the format Y-m-d.
     *
     * @return string
     */
    public function getTodaysDateString(): string
    {
        return (new DateTime())->format('Y-m-d');
    }

    /**
     * @return DateTime
     */
    public function getYesterdaysTimestamp(): DateTime
    {
        return (new DateTime())->add(DateInterval::createFromDateString('yesterday'));
    }

    /**
     * @return DateTime
     */
    public function getTomorrowsTimestamp(): DateTime
    {
        return (new DateTime())->add(DateInterval::createFromDateString('tomorrow'));
    }

    /**
     * @return DateTime
     */
    public function getNextYearsTimestamp(): DateTime
    {
        return (new DateTime())->add(DateInterval::createFromDateString('next year'));
    }

    //</editor-fold>

    //<editor-fold desc="DataProviders">

    /**
     * Provides valid keys.
     *
     * @return array
     */
    public function validKeysDataProvider(): array
    {
        return [
            'private sandbox key' => ['s-priv-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n'],
            'private production key' => ['p-priv-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n']
        ];
    }

    /**
     * Provides invalid keys.
     *
     * @return array
     */
    public function invalidKeysDataProvider(): array
    {
        return [
            'public sandbox key' => ['s-pub-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n'],
            'public production key' => ['p-pub-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n'],
            'invalid environment' => ['t-priv-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n'],
            'invalid key type' => ['s-xyz-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n'],
            'invalid format 1' => ['spriv-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n'],
            'invalid format 2' => ['2a102ZMq3gV4I3zJ888J7RR6u75oqK3n']
        ];
    }

    //</editor-fold>

    /** Assertion that all elements of an array (needle) do exist in a haystack.
     *
     * @param array $needle
     * @param array $haystack
     */
    protected function assertArrayContains(array $needle, array $haystack): void
    {
        foreach ($needle as $key => $value) {
            $this->assertEquals($value, $haystack[$key]);
        }
    }
}
