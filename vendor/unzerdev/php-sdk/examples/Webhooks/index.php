<?php
/**
 * This is the index controller for the Webhook tests.
 *
 * @link  https://docs.unzer.com/
 *
 */

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** @noinspection PhpIncludeInspection */
/** Require the composer autoloader file */
require_once __DIR__ . '/../../../../autoload.php';

use UnzerSDK\Constants\WebhookEvents;
use UnzerSDK\examples\ExampleDebugHandler;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Unzer;
use UnzerSDK\Resources\Webhook;

function printMessage($type, $title, $text)
{
    echo '<div class="ui ' . $type . ' message">'.
            '<div class="header">' . $title . '</div>'.
            '<p>' . nl2br($text) . '</p>'.
         '</div>';
}

function printError($text)
{
    printMessage('error', 'Error', $text);
}

function printSuccess($title, $text)
{
    printMessage('success', $title, $text);
}

function printInfo($title, $text)
{
    printMessage('info', $title, $text);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unzer UI Examples</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
            crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css" />
</head>

<body style="margin: 70px 70px 0;">
<div class="ui container segment">
    <h2 class="ui header">
        <i class="envelope outline icon"></i>
        <span class="content">
            Webhook registration
        </span>
    </h2>

    <?php
        try {
            $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
            $unzer->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

            $webhooks = $unzer->registerMultipleWebhooks(CONTROLLER_URL, [WebhookEvents::ALL]);

            foreach ($webhooks as $webhook) {
                /** @var Webhook $webhook */
                printSuccess(
                    'Event registered',
                    '<strong>Event:</strong> ' . $webhook->getEvent() . '</br>' .
                    '<strong>URL:</strong> ' . $webhook->getUrl() . '</br>'
                );
            }

            printInfo('You are ready to trigger events', 'Now Perform payments <a href="..">>> HERE <<</a> to trigger events!');

        } catch (UnzerApiException $e) {
            printError($e->getMessage());
            $unzer->debugLog('Error: ' . $e->getMessage());
        } catch (RuntimeException $e) {
            printError($e->getMessage());
            $unzer->debugLog('Error: ' . $e->getMessage());
        }
    ?>
</div>
</body>
</html>
