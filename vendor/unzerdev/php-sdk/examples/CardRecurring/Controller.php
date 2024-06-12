<?php
/**
 * This is the controller for the Card recurring example.
 * It is called when the pay button on the index page is clicked.
 *
 * @link  https://docs.unzer.com/
 *
 */

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** Require the composer autoloader file */
/** @noinspection PhpIncludeInspection */
require_once __DIR__ . '/../../../../autoload.php';

use UnzerSDK\Constants\RecurrenceTypes;
use UnzerSDK\examples\ExampleDebugHandler;
use UnzerSDK\Exceptions\UnzerApiException;
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

// Just for this example: Use selected recurrence type. Scheduled will be used as default.
$recurrenceType = $_POST['recurrence_type'] ?? RecurrenceTypes::SCHEDULED;

// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create an Unzer object using your private key and register a debug handler if you want to.
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());
    $paymentType = $unzer->fetchPaymentType($paymentTypeId);

    $charge = (new Charge(12.99, 'EUR', MY_RETURN_CONTROLLER_URL))
        ->setRecurrenceType($recurrenceType, $paymentType);

    $transaction = $unzer->performCharge($charge, $paymentTypeId);

    // You'll need to remember the paymentId for later in the ReturnController (in case of 3ds)
    $_SESSION['PaymentTypeId'] = $paymentTypeId;
    $_SESSION['ShortId'] = $transaction->getShortId();
    $_SESSION['recurrenceType'] = $transaction->getRecurrenceType();

    // Redirect to the 3ds page or to success depending on the state of the transaction
    $redirect = !empty($transaction->getRedirectUrl());
    if (!$redirect && $transaction->isSuccess()) {
        redirect(SUCCESS_URL);
    } elseif ($redirect && $transaction->isPending()) {
        redirect($transaction->getRedirectUrl());
    }

    // Check the result message of the transaction to find out what went wrong.
    $merchantMessage = $transaction->getMessage()->getCustomer();
} catch (UnzerApiException $e) {
    $merchantMessage = $e->getMerchantMessage();
    $clientMessage = $e->getClientMessage();
} catch (RuntimeException $e) {
    $merchantMessage = $e->getMessage();
}
redirect(FAILURE_URL, $merchantMessage, $clientMessage);
