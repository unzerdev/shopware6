<?php
/**
 * This file provides an example implementation of the Paylater Invoice payment type.
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

<p><a href="https://docs.unzer.com/reference/test-data" target="_blank">Click here to open our test data in new tab.</a></p>

<form id="payment-form" class="unzerUI form">
    <div id="customer" class="field">
        <!-- The customer form UI element will be inserted here -->
    </div>
    <div id="example-paylater-invoice"></div>
    <div class="field" id="error-holder" style="color: #9f3a38"></div>
    <div class="field">
        <button class="unzerUI primary button fluid" id="submit-button" type="submit">Pay</button>
    </div>
</form>

<script>
    // Create an Unzer instance with your public key
    let unzerInstance = new unzer('<?php echo UNZER_PAPI_PUBLIC_KEY; ?>');

    // Just for example purpose. Make sure to generate a unique ID.
    let threatMetrixId = 'php-sdk-example_' + Date.now();

    // Create an Paylater Invoice instance
    let paylaterInvoice = unzerInstance.PaylaterInvoice();
    paylaterInvoice.create({
        containerId: 'example-paylater-invoice',
        customerType: 'B2C',
        errorHolderId: 'error-holder',
        threatMetrixId: threatMetrixId
    });

    // Create a customer instance and render the customer form
    let Customer = unzerInstance.Customer();
    Customer.create({
        containerId: 'customer',
        differentBillingAddress: true,
        horizontalDivider: true,
        errorHolderId: 'error-holder',
        paymentTypeName: 'paylater-invoice'
    });

    // Handle payment form submission.
    let form = document.getElementById('payment-form');
    let payButton = document.getElementById("submit-button");
    payButton.disabled = true;

    let isValidCustomer = false;
    let isValidResource = false;
    paylaterInvoice.addEventListener('change', function eventHandlerResource(e) {
        if (e.success) {
            isValidResource = true;
            if (isValidCustomer) {
                $('button[type="submit"]').removeAttr('disabled');
            }
        } else {
            isValidResource = false;
            $('button[type="submit"]').attr('disabled', 'disabled');
        }
    })

    Customer.addEventListener('validate', function eventHandlerCustomer(e) {
        if (e.success) {
            isValidCustomer = true;
            if (isValidResource) {
                $('button[type="submit"]').removeAttr('disabled');
            }
        } else {
            $('button[type="submit"]').attr('disabled', 'disabled');
            isValidCustomer = false;
        }
    })

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        let paylaterInvoicePromise = paylaterInvoice.createResource();
        let customerPromise = Customer.createCustomer();
        Promise.all([paylaterInvoicePromise, customerPromise])
            .then(function(values) {
                let paymentType = values[0];
                let customer = values[1];
                let hiddenInputPaymentTypeId = document.createElement('input');
                hiddenInputPaymentTypeId.setAttribute('type', 'hidden');
                hiddenInputPaymentTypeId.setAttribute('name', 'paymentTypeId');
                hiddenInputPaymentTypeId.setAttribute('value', paymentType.id);
                form.appendChild(hiddenInputPaymentTypeId);

                let hiddenInputCustomerId = document.createElement('input');
                hiddenInputCustomerId.setAttribute('type', 'hidden');
                hiddenInputCustomerId.setAttribute('name', 'customerId');
                hiddenInputCustomerId.setAttribute('value', customer.id);
                form.appendChild(hiddenInputCustomerId);

                let threatMetrixIdInput = document.createElement('input');
                threatMetrixIdInput.setAttribute('type', 'hidden');
                threatMetrixIdInput.setAttribute('name', 'threatMetrixId');
                threatMetrixIdInput.setAttribute('value', threatMetrixId);
                form.appendChild(threatMetrixIdInput);

                form.setAttribute('method', 'POST');
                form.setAttribute('action', '<?php echo CONTROLLER_URL; ?>');

                // Submitting the form
                form.submit();
            })
            .catch(function(error) {
                $('#error-holder').html(error.customerMessage || error.message || 'Error')
            })
    });
</script>

</body>
</html>
