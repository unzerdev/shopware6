<?php
/**
 * This is the return controller for the PayPal recurring example.
 * It is called when the client is redirected back to the shop from the external page.
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
use UnzerSDK\Resources\PaymentTypes\Card;

session_start();

$clientMessage = 'Something went wrong. Please try again later.';
$merchantMessage = 'Something went wrong. Please try again later.';

function redirect($url, $merchantMessage = '', $clientMessage = '')
{
    $_SESSION['merchantMessage'] = $merchantMessage;
    $_SESSION['clientMessage']   = $clientMessage;
    header('Location: ' . $url);
    die();
}

// Retrieve the paymentId you remembered within the Controller
if (!isset($_SESSION['PaymentTypeId'])) {
    redirect(FAILURE_URL, 'The payment type id is missing.', $clientMessage);
}
$paymentTypeId = $_SESSION['PaymentTypeId'];

// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create an Unzer object using your private key and register a debug handler if you want to.
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

    // Redirect to success if the payment has been successfully completed or is still in handled.
    /** @var Card $paymentType */
    $paymentType = $unzer->fetchPaymentType($paymentTypeId);

    if ($paymentType->isRecurring()) {
        redirect(SUCCESS_URL);
    }

} catch (UnzerApiException $e) {
    $merchantMessage = $e->getMerchantMessage();
    $clientMessage = $e->getClientMessage();
} catch (RuntimeException $e) {
    $merchantMessage = $e->getMessage();
}

redirect(FAILURE_URL, $merchantMessage, $clientMessage);
