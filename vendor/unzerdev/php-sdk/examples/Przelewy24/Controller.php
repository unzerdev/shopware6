<?php
/**
 * This is the controller for the Przelewy24 (P24) example.
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

use UnzerSDK\examples\ExampleDebugHandler;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Unzer;
use UnzerSDK\Resources\CustomerFactory;

$clientMessage = 'Something went wrong. Please try again later.';

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

// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create an Unzer object using your private key and register a debug handler if you want to.
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

    // Create a charge to get the redirectUrl.
    $customer = CustomerFactory::createCustomer('Max', 'Mustermann');
    $charge   = $unzer->charge(12.99, 'PLN', $paymentTypeId, RETURN_CONTROLLER_URL, $customer);

    // You'll need to remember the paymentId for later in the ReturnController
    $_SESSION['PaymentId'] = $charge->getPaymentId();
    $_SESSION['ShortId']   = $charge->getShortId();

    // Redirect to the P24 page of the selected bank
    if (!$charge->isError() && $charge->getRedirectUrl() !== null) {
        redirect($charge->getRedirectUrl());
    }

    // Check the result message of the charge to find out what went wrong.
    $merchantMessage = $charge->getMessage()->getCustomer();
} catch (UnzerApiException $e) {
    $merchantMessage = $e->getMerchantMessage();
    $clientMessage = $e->getClientMessage();
} catch (RuntimeException $e) {
    $merchantMessage = $e->getMessage();
}
redirect(FAILURE_URL, $merchantMessage, $clientMessage);
