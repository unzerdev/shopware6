<?php

/*
 *  Controller for subsequent transactions.
 *
 *  @link  https://docs.unzer.com/
 *
 */

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** Require the composer autoloader file */
/** @noinspection PhpIncludeInspection */
require_once __DIR__ . '/../../../../autoload.php';

use UnzerSDK\examples\ExampleDebugHandler;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Unzer;

session_start();

$clientMessage = 'Something went wrong. Please try again later.';
$merchantMessage = 'Something went wrong. Please try again later.';
$debugHandler = new ExampleDebugHandler();

function redirect($url, $merchantMessage = '', $clientMessage = '')
{
    $_SESSION['merchantMessage'] = $merchantMessage;
    $_SESSION['clientMessage']   = $clientMessage;
    header('Location: ' . $url);
    die();
}

// You will need the id of the payment type created in the frontend (index.php)
if (!isset($_POST['payment_type_id'])) {
    redirect(FAILURE_URL, 'Resource id is missing!', $clientMessage);
}
$paymentTypeId   = $_POST['payment_type_id'];

// Reuse the recurrence type of the recurring transaction, if set.
$recurrenceTyp = $_SESSION['recurrenceType'] ?? null;

// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create an Unzer object using your private key and register a debug handler if you want to.
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler($debugHandler);

    $customer = CustomerFactory::createCustomer('Max', 'Mustermann');
    $customer->setEmail('test@test.com');

    $transaction = $unzer->charge(12.99, 'EUR', $paymentTypeId, RETURN_CONTROLLER_URL, $customer, null, null, null, true, null, null, $recurrenceTyp);

    // You'll need to remember the paymentId for later in the ReturnController (in case of 3ds)
    $_SESSION['PaymentTypeId'] = $paymentTypeId;
    $_SESSION['ShortId'] = $transaction->getShortId();

    // Redirect to the failure page or to success depending on the state of the transaction
    $redirect = !empty($transaction->getRedirectUrl());
    if (!$redirect && $transaction->isSuccess()) {
        redirect(SUCCESS_URL);
    } elseif ($redirect && $transaction->isPending()) {
        redirect(FAILURE_URL, 'Transaction initiated by merchant should not redirect to 3ds Page. The customer needs to
        do the 3ds authentication first for that payment type.');
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
