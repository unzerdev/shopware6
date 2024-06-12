<?php
/**
 * This is the controller for the Apple Pay example.
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

// These lines are just for this example
$jsonData      = json_decode(file_get_contents('php://input'), false);
$paymentTypeId = $jsonData->typeId;
$transactionType = $jsonData->transaction_type ?? 'authorize';

// You will need the id of the payment type created in the frontend (index.php)
if (empty($paymentTypeId)) {
    echo json_encode(['result' => false]);
    return;
}

// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create an Unzer object using your private key and register a debug handler if you want to.
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

    switch ($transactionType) {
        case 'charge':
            $transaction = $unzer->charge(12.99, 'EUR', $paymentTypeId, RETURN_CONTROLLER_URL);
            break;
        default:
            $transaction = $unzer->authorize(12.99, 'EUR', $paymentTypeId, RETURN_CONTROLLER_URL);
            break;
    }

    // You'll need to remember the paymentId for later in the ReturnController
    $_SESSION['PaymentId'] = $transaction->getPaymentId();
    $_SESSION['ShortId'] = $transaction->getShortId();

    if ($transaction->isSuccess()) {
        echo json_encode(['transactionStatus' => 'success']);
        return;
    }
    if ($transaction->isPending()) {
        echo json_encode(['transactionStatus' => 'pending']);
        return;
    }
} catch (UnzerApiException $e) {
    $_SESSION['merchantMessage'] = $e->getMerchantMessage();
    $_SESSION['clientMessage'] = $e->getClientMessage();
} catch (RuntimeException $e) {
    $_SESSION['merchantMessage'] = $e->getMessage();
}

echo json_encode(['transactionStatus' => 'error']);
