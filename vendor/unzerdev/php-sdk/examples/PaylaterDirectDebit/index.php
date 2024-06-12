<?php

/**
 * This file provides an example implementation of the Paylater Direct Debit payment type.
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
    <meta charset="UTF-8"/>
    <title>Unzer UI Examples</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
            crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://static.unzer.com/v1/unzer.css"/>
    <script type="text/javascript" src="https://static.unzer.com/v1/unzer.js"></script>
</head>

<body style="margin: 70px 70px 0;">

<p><a href="https://docs.unzer.com/reference/test-data" target="_blank">Click here to open our test data in new tab.</a><br/>
</p>

<div id="dimmer-holder-pdd" class="ui active dimmer" style="display: none;">
    <div class="ui loader"></div>
</div>
<form id="payment-form-paylater-direct-debit" class="unzerUI form" novalidate>
    <div id="example-pdd" class="field"></div>

    <div id="error-holder-pdd" class="field" style="color: #9f3a38"></div>

    <div class="field">
        <button class="unzerUI primary button fluid" disabled type="submit">Pay</button>
    </div>
</form>

<script>
    // Create an Unzer instance with your public key
    let unzerInstance = new unzer('<?php echo UNZER_PAPI_PUBLIC_KEY; ?>');
    let paylaterDirectDebit = unzerInstance.PaylaterDirectDebit();

    // Just for example purpose. Make sure to generate a unique ID.
    let threatMetrixId = 'php-sdk-example_' + Date.now();

    paylaterDirectDebit.create('paylater-direct-debit', {
        containerId: 'example-pdd',
        threatMetrixId: threatMetrixId
    });

    var hpDimmer = document.getElementById('pit-dimmer-holder')
    var continueButton = document.getElementById('continue-button')
    var form = document.getElementById('payment-form-paylater-direct-debit')

    var buttonDisabled = true

    var eventHandlerSepaInput = function (e) {
        if (e.success) {
            buttonDisabled = false
        } else {
            buttonDisabled = true
        }
        if (!buttonDisabled) {
            $('button[type="submit"]').removeAttr('disabled');
        } else {
            $('button[type="submit"]').attr('disabled', 'disabled');
        }
    }

    paylaterDirectDebit.addEventListener('change', eventHandlerSepaInput);


    // Handling the form's submission.
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        paylaterDirectDebit.createResource()
            .then(function (data) {
                let hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'paymentTypeId');
                hiddenInput.setAttribute('value', data.id);
                form.appendChild(hiddenInput);

                let threatMetrixIdInput = document.createElement('input');
                threatMetrixIdInput.setAttribute('type', 'hidden');
                threatMetrixIdInput.setAttribute('name', 'threatMetrixId');
                threatMetrixIdInput.setAttribute('value', threatMetrixId);
                form.appendChild(threatMetrixIdInput);

                form.setAttribute('method', 'POST');
                form.setAttribute('action', '<?php echo CONTROLLER_URL; ?>');
                form.submit();
            })
            .catch(function (error) {
                $('#error-holder').html(error.message)
            });
    });
</script>

</body>
</html>
