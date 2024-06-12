<?php

/**
 * This file provides an example implementation of the Googlepay payment type.
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
    <script src="https://pay.google.com/gp/p/js/pay.js"></script>

</head>

<body style="margin: 70px 70px 0;">

<p><a href="https://docs.unzer.com/reference/test-data" target="_blank">Click here to open our test data in new tab.</a><br/>
</p>

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

<div id="dimmer-holder-googlepay" class="ui active dimmer" style="display: none;">
    <div class="ui loader"></div>
</div>

<div id="example-googlepay-stack"></div>
<div id="error-holder-googlepay" class="field" style="text-align: center; color: #9f3a38"></div>

<script>
    const unzerInstance = new unzer('<?php echo UNZER_PAPI_PUBLIC_KEY; ?>');
    const colors = ['black','white'];
    const googlepayChannelId = "<?php echo UNZER_EXAMPLE_GOOGLEPAY_CHANNEL; ?>"
    const stackHolder = document.querySelector('#example-googlepay-stack');

    const tmpPaymentDataRequestObject = {
        gatewayMerchantId: googlepayChannelId,
        allowedCardNetworks: [
            'MASTERCARD',
            'VISA',
            'DISCOVER',
            'JCB',
        ],
        merchantInfo: {
            merchantId: googlepayChannelId,
            merchantName: 'Example Merchant'
        },
        transactionInfo: {
            displayItems: [],
            countryCode: 'DE',
            currencyCode: 'EUR',
            totalPrice: '12.00',
        },
    }

    function handleGooglepayError(error) {
        let errorMessage = error.customerMessage || error.message || 'Error';
        if (error.data && Array.isArray(error.data.errors) && error.data.errors[0]) {
            errorMessage = error.data.errors[0].customerMessage || 'Error'
        }

        document.getElementById('error-holder-googlepay').innerHTML = errorMessage;

        return {
            status: 'error',
            message: errorMessage || 'Unexpected error'
        }
    }

    colors.map(function (color) {
        const htmlString = '<div id="example-googlepay-' + color + '" class="field" style="text-align: center; margin: 5px 0"></div>'
        return ({
            instance: null,
            color: color,
            htmlString: htmlString
        })
    }).forEach(function (item) {
        stackHolder.insertAdjacentHTML('beforeend', item.htmlString)
        item.instance = unzerInstance.Googlepay()
        const extendedPaymentDataRequestObject = {
            ...tmpPaymentDataRequestObject,
            buttonColor: item.color,
            onPaymentAuthorizedCallback: (paymentData) => {
                document.getElementById('error-holder-googlepay').innerHTML = ''
                const $form = $('form[id="payment-form"]');
                const formObject = QueryStringToObject($form.serialize());

                return item.instance.createResource(paymentData)
                    .then(typeCreationResult => {
                        document.getElementById('dimmer-holder-googlepay').style.display = 'block';
                        formObject.typeId = typeCreationResult.id;

                        return fetch(
                            './Controller.php',
                            {
                                body: JSON.stringify(formObject),
                                method: 'POST'
                            }
                        )
                            .then(response => response.json())
                            .then( transactionResult => {
                                const status = transactionResult.transactionStatus;

                                if (status === 'success' || status === 'pending') {
                                    if (transactionResult.redirectUrl.trim().length !== 0) {
                                        window.location.href = transactionResult.redirectUrl;
                                    } else {
                                        window.location.href = '<?php echo RETURN_CONTROLLER_URL; ?>';
                                    }
                                    return { status: 'success' };
                                }
                                window.location.href = '<?php echo FAILURE_URL; ?>';
                                return {
                                    status: 'error',
                                    message: transactionResult.customerMessage || transactionResult.message || 'Unexpected error'
                                }
                            })
                        .catch(function (error) {
                                return handleGooglepayError(error);
                            }
                        )
                    })
                    .catch(function (error) {
                        return handleGooglepayError(error);
                    })
            }
        }

        const paymentDataRequestObject = item.instance.initPaymentDataRequestObject(extendedPaymentDataRequestObject)
        item.instance.create(
            {
                containerId: 'example-googlepay-' + item.color
            },
            paymentDataRequestObject
        )
    })

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

</script>

</body>
</html>
