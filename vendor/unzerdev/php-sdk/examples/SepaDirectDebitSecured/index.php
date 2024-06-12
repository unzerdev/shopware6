<?php
/**
 * This file provides an example implementation of the SEPA direct debit secured payment type.
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
    <div id="sepa-secured-IBAN" class="field">
        <!-- The IBAN field UI Element will be inserted here -->
    </div>
    <div id="customer" class="field">
        <!-- The customer form UI element will be inserted here -->
    </div>
    <div class="field" id="error-holder" style="color: #9f3a38"></div>
    <div class="field">
        <button class="unzerUI primary button fluid" id="submit-button" type="submit">Pay</button>
    </div>
</form>

<script>
    // Create an Unzer instance with your public key
    let unzerInstance = new unzer('<?php echo UNZER_PAPI_PUBLIC_KEY; ?>');

    // Create a SEPA Direct Debit Secured instance and render the form
    let SepaDirectDebitSecured = unzerInstance.SepaDirectDebitSecured();
    SepaDirectDebitSecured.create('sepa-direct-debit-secured', {
        containerId: 'sepa-secured-IBAN'
    });

    // Creat a customer instance and render the form
    let Customer = unzerInstance.Customer();
    Customer.create({
        containerId: 'customer'
    });

    // Handle payment form submission.
    let form = document.getElementById('payment-form');
    let payButton = document.getElementById("submit-button");

    payButton.disabled = true;

    let isValidCustomer = false;
    let isValidResource = false;
    SepaDirectDebitSecured.addEventListener('change', function eventHandlerResource(e) {
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
        let sepaDirectDebitSecuredPromise = SepaDirectDebitSecured.createResource();
        let customerPromise = Customer.createCustomer();
        Promise.all([sepaDirectDebitSecuredPromise, customerPromise])
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
