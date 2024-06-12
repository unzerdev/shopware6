<?php
/**
 * This is the controller for the Klarna example.
 * It is called when the pay button on the index page is clicked.
 *
 * @link  https://docs.unzer.com/
 *
 */

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** @noinspection PhpIncludeInspection */
/** Require the composer autoloader file */
require_once __DIR__ . '/../../../../autoload.php';

use UnzerSDK\Constants\Salutations;
use UnzerSDK\Constants\ShippingTypes;
use UnzerSDK\examples\ExampleDebugHandler;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\Address;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Unzer;

session_start();
session_unset();

$clientMessage = 'Something went wrong. Please try again later.';
$merchantMessage = 'Something went wrong. Please try again later.';

function redirect($url, $merchantMessage = '', $clientMessage = '')
{
    $_SESSION['merchantMessage'] = $merchantMessage;
    $_SESSION['clientMessage']   = $clientMessage;
    header('Location: ' . $url);
    die();
}

// You will need the id of the payment type created in the frontend (index.php)
if (!isset($_POST['resourceId'])) {
    redirect(FAILURE_URL, 'Resource id is missing!', $clientMessage);
}
$paymentTypeId   = $_POST['resourceId'];

$transactionType = $_POST['transaction_type'] ?? 'authorize';

// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create an Unzer object using your private key and register a debug handler if you want to.
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

    $orderId = 'o' . str_replace(['0.', ' '], '', microtime(false));

    // A Basket is mandatory for Invoice Secured payment type
    $basketItem = (new BasketItem())
        ->setAmountPerUnitGross(119.00)
        ->setVat(19.00)
        ->setQuantity(1)
        ->setBasketItemReferenceId('item1')
        ->setTitle('Hat');

    $basket = new Basket($orderId);
    $basket->setTotalValueGross(119.00)
        ->addBasketItem($basketItem)
        ->setCurrencyCode('EUR');

    // A customer is mandatory for Klarna payment type
    $address  = (new Address())
        ->setName('Max Universum')
        ->setStreet('Hugo-Junkers-Str. 4')
        ->setZip('60386')
        ->setCity('Frankfurt am Main')
        ->setCountry('DE')
        ->setState('DE-BO')
        ->setShippingType(ShippingTypes::EQUALS_BILLING);

    $customer = CustomerFactory::createCustomer('Peter', 'Universum')
        ->setSalutation(Salutations::MR)
        ->setCompany('Unzer GmbH')
        ->setBirthDate('1989-12-24')
        ->setEmail('peter.universum@universum-group.de')
        ->setMobile('+49172123456')
        ->setPhone('+4962216471100')
        ->setBillingAddress($address)
        ->setLanguage('de');

    $transactionData = [119.00, 'EUR', RETURN_CONTROLLER_URL];
    $additionalTransactionData = [
        'termsAndConditionUrl' => 'https://www.unzer.com/de/',
        'privacyPolicyUrl' => 'https://www.unzer.com/de/'
    ];

    // Create a authorize transaction to get the redirectUrl.
    $authorizationInstance = (new Authorization(...$transactionData))
        ->setAdditionalTransactionData((object)$additionalTransactionData);
    $transaction = $unzer->performAuthorization($authorizationInstance, $paymentTypeId, $customer, null, $basket);

    // You'll need to remember the paymentId for later in the ReturnController
    $_SESSION['PaymentId'] = $transaction->getPaymentId();
    $_SESSION['ShortId']   = $transaction->getShortId();
    $_SESSION['isAuthorizeTransaction'] = true;

    // Redirect to the Klarna page
    if (!$transaction->isError() && $transaction->getRedirectUrl() !== null) {
        redirect($transaction->getRedirectUrl());
    }

    // Check the result message of the charge to find out what went wrong.
    $merchantMessage = $transaction->getMessage()->getCustomer();
} catch (UnzerApiException $e) {
    $merchantMessage = $e->getMerchantMessage();
    $clientMessage = $e->getClientMessage();
} catch (RuntimeException $e) {
    $merchantMessage = $e->getMessage();
}
redirect(FAILURE_URL, $merchantMessage, $clientMessage);
