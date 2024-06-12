<?php
/**
 * This is the controller for the Webhook reception tests.
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

try {
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

    $resource = $unzer->fetchResourceFromEvent(file_get_contents('php://input'));
    $unzer->debugLog('Fetched resource from Event: ' . $resource->jsonSerialize());
} catch (UnzerApiException $e) {
    $unzer->debugLog('Error: ' . $e->getMessage());
} catch (RuntimeException $e) {
    $unzer->debugLog('Error: ' . $e->getMessage());
}
