<?php
/**
 * This is the controller for the Card example.
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

// These lines are just for this example
$transactionType = $_POST['transaction_type'] ?? 'authorize';
$use3Ds          = isset($_POST['3dsecure']) && ($_POST['3dsecure'] === '1');

// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create an Unzer object using your private key and register a debug handler if you want to.
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

    // Create a charge/authorize transaction
    // The 3D secured flag can be used to switch between 3ds and non-3ds.
    // If your merchant is only configured for one of those you can omit the flag.
    $customer = CustomerFactory::createCustomer('Max', 'Mustermann');
    $customer->setEmail('test@test.com');

    switch ($transactionType) {
        case 'charge':
            $transaction = $unzer->charge(12.99, 'EUR', $paymentTypeId, RETURN_CONTROLLER_URL, $customer, null, null, null, $use3Ds);
            break;
        case 'payout':
            $transaction = $unzer->payout(12.99, 'EUR', $paymentTypeId, RETURN_CONTROLLER_URL, $customer);
            break;
        default:
            $transaction = $unzer->authorize(12.99, 'EUR', $paymentTypeId, RETURN_CONTROLLER_URL, $customer, null, null, null, $use3Ds);
            break;
    }

    // You'll need to remember the paymentId for later in the ReturnController (in case of 3ds)
    $_SESSION['PaymentId'] = $transaction->getPaymentId();
    $_SESSION['ShortId'] = $transaction->getShortId();

    // Redirect to the 3ds page or to success depending on the state of the transaction
    $payment  = $transaction->getPayment();
    $redirect = !empty($transaction->getRedirectUrl());

    switch (true) {
        case (!$redirect && $transaction->isSuccess()):
            redirect(SUCCESS_URL);
            break;
        case (!$redirect && $transaction->isPending()):
            redirect(PENDING_URL);
            break;
        case ($redirect && $transaction->isPending()):
            redirect($transaction->getRedirectUrl());
            break;
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
