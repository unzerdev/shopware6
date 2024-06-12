<?php
/**
 * This file provides an example implementation of the PayPal payment type.
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
            crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://static.unzer.com/v1/unzer.css" />
    <script type="text/javascript" src="https://static.unzer.com/v1/unzer.js"></script>
</head>

<body style="margin: 70px 70px 0;">
<h3>Example data:</h3>
<ul>
    <li>Username: paypal-buyer@unzer.com</li>
    <li>Password: unzer1234</li>
</ul>
<strong>Attention:</strong> We recommend to create your own PayPal test account <a href="https://developer.paypal.com" target="_blank">here</a>.

<p><a href="https://docs.unzer.com/reference/test-data" target="_blank">Click here to open our test data in new tab.</a></p>

<form id="payment-form" class="unzerUI" novalidate>
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
    <h3>PayPal</h3>
    <div id="container-example-paypal"></div>
    <div class="field" id="error-holder" style="color: #9f3a38"> </div>
    <div class="field">
        <button class="unzerUI primary button fluid" id="submit-button" type="submit">Pay</button>
    </div>

    <h3>PayPal Express</h3>
    <div id="container-example-paypal-express"></div>
</form>

<script>
    // Create an Unzer instance with your public key
    let unzerInstance = new unzer('<?php echo UNZER_PAPI_PUBLIC_KEY; ?>');

    // Create a normal Paypal instance
    let paypalInstance = unzerInstance.Paypal();
    paypalInstance.create('email', {
        containerId: 'container-example-paypal'
    })

    // Create a Paypal Express instance
    let paypalExpress = unzerInstance.PaypalExpress();
    paypalExpress.create({
        containerId: 'container-example-paypal-express',
        color: 'gold'
    })

    // Handle payment form submission
    let form = document.getElementById('payment-form');
    form.addEventListener('submit', function(event) {
        event.preventDefault();

        // If the express button submitted the form use the PaypalExpress instance.
        if (event.submitter.id === 'pay-button') {
            paypalInstance = paypalExpress;

            // Create an additional input so that backend can set the checkout type for the transaction.
            let expressCheckout = document.createElement('input');
            expressCheckout.setAttribute('type', 'hidden');
            expressCheckout.setAttribute('name', 'express-checkout');
            expressCheckout.setAttribute('value', "1");
            form.appendChild(expressCheckout);
        }

        // Creating a PayPal or PayPal express resource
        paypalInstance.createResource()
            .then(function(result) {
                let hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'resourceId');
                hiddenInput.setAttribute('value', result.id);
                form.appendChild(hiddenInput);
                form.setAttribute('method', 'POST');
                form.setAttribute('action', '<?php echo CONTROLLER_URL; ?>');

                // Submitting the form
                form.submit();
            })
            .catch(function(error) {
                $('#error-holder').html(error.message)
            })
    });
</script>
</body>
</html>
