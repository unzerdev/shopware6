<?php
/**
 * This file provides an example implementation of the Apple Pay payment type.
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
    <style>
        .apple-pay-button {
            display: inline-block;
            -webkit-appearance: -apple-pay-button;
            -apple-pay-button-type: buy;
            -apple-pay-button-style: black;
        }

        .button-well {
            text-align: center;
            position: relative;
            background: #f1f1f1;
            border-radius: 3px;
            -webkit-transition: background 0.3s;
            transition: background 0.3s;
            margin: 10px;
            border: 1px solid transparent;
        }

        .unsupportedBrowserMessage {
            color: #888;
            padding: .375rem .75rem;
            cursor: not-allowed;
            line-height: 1.5;
        }

        .unsupportedBrowserMessage p {
            margin: 0;
        }

        .applePayButtonContainer {
            position: relative;
        }
    </style>
</head>

<body style="margin: 70px 70px 0;">

<p><a href="https://docs.unzer.com/docs/testdata" target="_blank">Click here to open our test data in new tab.</a></p>

<form id="payment-form" class="unzerUI form" novalidate>
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

    <div>
        <div class="field" id="error-holder" style="color: #9f3a38"> </div>
        <div class="button-well">
            <div class="applePayButtonContainer">
                <div class="apple-pay-button apple-pay-button-black" lang="us"
                     onclick="setupApplePaySession()"
                     title="Start Apple Pay" role="link" tabindex="0">
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    const $errorHolder = $('#error-holder');

    const unzerInstance = new unzer('<?php echo UNZER_PAPI_PUBLIC_KEY; ?>');
    const unzerApplePayInstance = unzerInstance.ApplePay();

    function startApplePaySession(applePayPaymentRequest) {
        if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
            const session = new ApplePaySession(6, applePayPaymentRequest);
            session.onvalidatemerchant = function (event) {
                merchantValidationCallback(session, event);
            };

            session.onpaymentauthorized = function (event) {
                applePayAuthorizedCallback(event, session);
            };

            session.oncancel = function (event) {
                onCancelCallback(event);
            };

            session.begin();
        } else {
            handleError("This device does not support Apple Pay!");
        }
    }

    function applePayAuthorizedCallback(event, session) {
        // Get payment data from event. "event.payment" also contains contact information, if they were set via Apple Pay.
        const paymentData = event.payment.token.paymentData;
        const $form = $('form[id="payment-form"]');
        const formObject = QueryStringToObject($form.serialize());

        // Create an Unzer instance with your public key
        unzerApplePayInstance.createResource(paymentData)
            .then(function (createdResource) {
                formObject.typeId = createdResource.id;
                // Hand over the type ID to your backend.
                $.post('./Controller.php', JSON.stringify(formObject), null, 'json')
                    .done(function (result) {
                        // Handle the transaction respone from backend.
                        const status = result.transactionStatus;
                        if (status === 'success' || status === 'pending') {
                            session.completePayment({status: window.ApplePaySession.STATUS_SUCCESS});
                            window.location.href = '<?php echo RETURN_CONTROLLER_URL; ?>';
                        } else {
                            window.location.href = '<?php echo FAILURE_URL; ?>';
                            abortPaymentSession(session);
                            session.abort();
                        }
                    })
                    .fail(function (error) {
                        handleError(error.statusText);
                        abortPaymentSession(session);
                    });
            })
            .catch(function (error) {
                handleError(error.message);
                abortPaymentSession(session);
            })
    }

    function merchantValidationCallback(session, event) {
        $.post('./merchantvalidation.php', JSON.stringify({"merchantValidationUrl": event.validationURL}), null, 'json')
            .done(function (validationResponse) {
                try {
                    session.completeMerchantValidation(validationResponse);
                } catch (e) {
                    alert(e.message);
                }

            })
            .fail(function (error) {
                handleError(JSON.stringify(error.statusText));
                session.abort();
            });
    }

    function onCancelCallback(event) {
        handleError('Canceled by user');
    }

    // Get called when pay button is clicked. Prepare ApplePayPaymentRequest and call `startApplePaySession` with it.
    function setupApplePaySession() {
        const applePayPaymentRequest = {
            countryCode: 'DE',
            currencyCode: "EUR",
            total: {
                label: 'Unzer gmbh',
                amount: 12.99
            },
            supportedNetworks: ['amex', 'visa', 'masterCard', 'discover'],
            merchantCapabilities: ['supports3DS', 'supportsCredit', 'supportsDebit'],
            requiredShippingContactFields: ['postalAddress', 'name', 'phone', 'email'],
            requiredBillingContactFields: ['postalAddress', 'name', 'phone', 'email'],
            lineItems: [
                {
                    "label": "Bag Subtotal",
                    "type": "final",
                    "amount": "10.00"
                },
                {
                    "label": "Free Shipping",
                    "amount": "0.00",
                    "type": "final"
                },
                {
                    "label": "Estimated Tax",
                    "amount": "2.99",
                    "type": "final"
                }
            ]
        };

        startApplePaySession(applePayPaymentRequest);
    }

    // Updates the error holder with the given message.
    function handleError (message) {
        $errorHolder.html(message);
    }

    // Translates query string to object
    function QueryStringToObject(queryString) {
        const pairs = queryString.slice().split('&');
        let result = {};

        pairs.forEach(function(pair) {
            pair = pair.split('=');
            result[pair[0]] = decodeURIComponent(pair[1] || '');
        });
        return JSON.parse(JSON.stringify(result));
    }

    // abort current payment session.
    function abortPaymentSession(session) {
        session.completePayment({status: window.ApplePaySession.STATUS_FAILURE});
        session.abort();
    }
</script>
</body>
</html>
