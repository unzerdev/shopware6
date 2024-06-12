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

use UnzerSDK\Adapter\ApplepayAdapter;
use UnzerSDK\Exceptions\ApplepayMerchantValidationException;
use UnzerSDK\Resources\ExternalResources\ApplepaySession;
use UnzerSDK\examples\ExampleDebugHandler;
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

// Get the merchant validation URL from the frontend.
$jsonData = json_decode(file_get_contents('php://input'), true);
$merchantValidationURL = urldecode($jsonData['merchantValidationUrl']);

// Do the merchant validation request and return the result to the frontend.
try {
    // Create an Unzer object using your private key and register a debug handler if you want to.
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

    /*
     * Just for demonstration purpose.
     * It is NOT RECOMMENDED to get get the domain name this way on a production environment, because of security reasons.
     */
    $domainName = $_SERVER['HTTP_HOST'];

    $applepaySession = new ApplepaySession(UNZER_EXAMPLE_APPLEPAY_MERCHANT_IDENTIFIER, 'PHP-SDK Example', $domainName);
    $appleAdapter = new ApplepayAdapter();

    $appleAdapter->init(UNZER_EXAMPLE_APPLEPAY_MERCHANT_CERT, UNZER_EXAMPLE_APPLEPAY_MERCHANT_CERT_KEY);

    // Send the applepay validation request.
    $validationResponse = $appleAdapter->validateApplePayMerchant($merchantValidationURL, $applepaySession);

    // Return the validation response to your frontend.
    print_r($validationResponse, 0);
} catch (RuntimeException | ApplepayMerchantValidationException $e) {
    // Dont give internal error directly to the frontend.
    throw new Exception('There has been an error validating the merchant. Please try again later.');
}
