<?php
/**
 * This file provides an example implementation of the  Embedded Payment Page.
 *
 * @link  https://docs.unzer.com/
 *
 */

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** @noinspection PhpIncludeInspection */

/** Require the composer autoloader file */
require_once __DIR__ . '/../../../../autoload.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unzer UI Examples</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.3.0/jquery.form.min.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://static.unzer.com/v1/unzer.css" />
    <script type="text/javascript" src="https://static.unzer.com/v1/unzer.js"></script>
    <script type="text/javascript" src="https://static.unzer.com/v1/checkout.js"></script>
</head>

<body>

<form id="payment-form" class="unzerUI form" novalidate style="margin: 70px 70px 0;">
    <p><a href="https://docs.unzer.com/reference/test-data" target="_blank">Click here to open our test data in new tab.</a></p>
    <!-- This is just for the example - Start -->
    <div class="fields inline">
        <label for="transaction_type">Chose the transaction type you want to test:</label>
        <div class="field">
            <div class="unzerUI radio checkbox">
                <input type="radio" name="transaction_type" value="authorize" checked="">
                <label>Authorize</label>
            </div>
        </div>
        <div class="field">
            <div class="unzerUI radio checkbox">
                <input type="radio" name="transaction_type" value="charge">
                <label>Charge</label>
            </div>
        </div>
    </div>
    <!-- This is just for the example - End -->

    <div class="field" id="error-holder" style="color: #9f3a38"> </div>

    <!-- The Payment Page needs to be initialized using the private key, that means it can only be done with a Server-To-Server call.
    Therefore we redirect to the controller without doing anything here. -->
    <div class="field">
        <button class="unzerUI primary button fluid" id="submit-button" type="submit">Pay</button>
    </div>
</form>

<script>
    // Create an Unzer instance with your public key
    // This is not actually needed for this example but we want the sandbox banner to show on the page.
    let unzerInstance = new unzer('<?php echo UNZER_PAPI_PUBLIC_KEY; ?>');

    let $errorHolder = $('#error-holder');
    let $submitButton = $('#submit-button');
    let $form = $('#payment-form');

    $form.ajaxForm(
        {
            beforeSubmit: (function () {
                $submitButton.attr("disabled", true);
                $errorHolder.html('');
            }),
            url: '<?php echo CONTROLLER_URL; ?>',
            type: 'post',
            success: function (response) {
                var checkout = new window.checkout(response.token);
                // Initialize the payment page
                checkout.init().then(function() {
                    // On success open the dialogue
                    checkout.open();

                    // Add your event listeners for abort or success event
                    checkout.abort(function() {
                        $errorHolder.html('Transaction canceled by user.');
                        $submitButton.attr("disabled", false);
                    });
                    checkout.success(function(data) {
                        // redirect to result handler
                        window.location.href = '<?php echo RETURN_CONTROLLER_URL; ?>';
                    });
                    checkout.error(function() {
                        $errorHolder.html('Transaction Failure');
                    });

                }).catch(function(error) {
                    // handle error on init
                    $errorHolder.html(error.message);
                    $submitButton.attr("disabled", false);
                });
            },
            error: function (response) {
                var responseJson = response.responseJSON;
                $errorHolder.html(responseJson.customer);
                $submitButton.attr("disabled", false);
            }
        });

</script>
</body>
</html>
