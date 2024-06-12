<?php

/*
 *  Controller for charge on payment (capture).
 *
 *  @link  https://docs.unzer.com/
 *
 */

/** Require the constants of this example */
require_once __DIR__ . '/../Constants.php';

/** Require the composer autoloader file */
/** @noinspection PhpIncludeInspection */
require_once __DIR__ . '/../../../../autoload.php';

use UnzerSDK\examples\ExampleDebugHandler;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\TransactionTypes\Charge;
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
if (!isset($_POST['payment_id'])) {
    redirect(FAILURE_URL, 'Resource id is missing!', $clientMessage);
}
$paymentId   = $_POST['payment_id'];

// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create an Unzer object using your private key and register a debug handler if you want to.
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler($debugHandler);

    $transaction = $unzer->performChargeOnPayment($paymentId, new Charge());

    // You'll need to remember the paymentId for later in the ReturnController (in case of 3ds)
    $_SESSION['ShortId'] = $transaction->getShortId();
    unset($_SESSION['isAuthorizeTransaction']);

    // Redirect to the failure page or to success depending on the state of the transaction
    $redirect = !empty($transaction->getRedirectUrl());
    if (!$redirect && $transaction->isSuccess()) {
        $_SESSION['additionalPaymentInformation'] = '<p>Charge was successful.</p>';
        redirect(BACKEND_URL);
    } elseif ($redirect && $transaction->isPending()) {
        redirect(BACKEND_FAILURE_URL, 'Transaction initiated by merchant should not redirect to 3ds Page. The customer needs to
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
redirect(BACKEND_FAILURE_URL, $merchantMessage, $clientMessage);
