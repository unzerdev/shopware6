<?php
/**
 * This file provides a list of the example implementations.
 *
 * @link  https://docs.unzer.com/
 *
 */

use UnzerSDK\Validators\PrivateKeyValidator;
use UnzerSDK\Validators\PublicKeyValidator;

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** @noinspection PhpIncludeInspection */
/** Require the composer autoloader file */
require_once __DIR__ . '/../../../autoload.php';

function printMessage($type, $title, $text)
{
    echo '<div class="ui ' . $type . ' message">'.
        '<div class="header">' . $title . '</div>'.
        '<p>' . nl2br($text) . '</p>'.
        '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Unzer UI Examples</title>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"
                integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css" />

        <link rel="stylesheet" href="https://static.unzer.com/v1/unzer.css" />
        <script type="text/javascript" src="https://static.unzer.com/v1/unzer.js"></script>
    </head>

    <body style="margin: 30px 70px 0;">
        <div class="ui container segment">
            <h2 class="ui header">
                <i class="shopping cart icon"></i>
                <span class="content">
                    Payment Implementation Examples
                    <span class="sub header">Choose the Payment Type you want to evaluate ...</span>
                </span>
            </h2>

            <?php
                // Show info message if the key pair is invalid
                if (
                    !PrivateKeyValidator::validate(UNZER_PAPI_PRIVATE_KEY) ||
                    !PublicKeyValidator::validate(UNZER_PAPI_PUBLIC_KEY)
                ) {
                    printMessage(
                        'yellow',
                        'Attention: You need to provide a valid key pair!',
                        "The key pair provided in file _enableExamples.php does not seem to be valid.\n".
                        'Please contact our support to get a test key pair <a href="mailto:support@unzer.com">support@unzer.com</a>'
                    );
                }
            ?>

            <div class="ui four cards">
                <div class="card olive">
                    <div class="content">
                        <div class="header">Apple Pay</div>
                        <div class="description">
                            You can try authorize and charge transactions.
                            Please make sure to provide the path to the certificates for this payment type.
                            Notes:
                            <ul>
                                <li>This payment type is available for Apple devices only.</li>
                                <li>Please refer to <a href="https://developer.apple.com/videos/play/tutorials/configuring-your-developer-account-for-apple-pay/" target="_blank">this page</a> to learn all about the requirements for Apple Pay.</li>
                            </ul>
                        </div>
                    </div>
                    <div id="tryApplePayExample" class="ui bottom attached green button" onclick="location.href='Applepay/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">Card</div>
                        <div class="description">
                            You can try authorize, charge and payout transactions with or without 3Ds.
                            This example submits email <b>via customer</b> resource.
                        </div>
                    </div>
                    <div id="tryCardExample" class="ui bottom attached green button" onclick="location.href='Card/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">Card (extended)</div>
                        <div class="description">
                            Including email and holder fields.
                            Adding more information to the card can improve risk acceptance.
                            This example submits email <b>via payment type</b>  resource.
                        </div>
                    </div>
                    <div id="tryCardExample" class="ui bottom attached green button" onclick="location.href='CardExtended/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">Card Recurring</div>
                        <div class="description">
                            You can set a Card type to recurring in order to register it and charge later as well as implement recurring payments.
                        </div>
                    </div>
                    <div id="tryCardRecurringExample" class="ui bottom attached green button" onclick="location.href='CardRecurring/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            EPS
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryEPSExample" class="ui bottom attached green button" onclick="location.href='EPSCharge/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            iDeal
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryIDealExample" class="ui bottom attached green button" onclick="location.href='IDeal/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Giropay
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryGiropayExample" class="ui bottom attached green button" onclick="location.href='Giropay/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Google Pay
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryGooglepayExample" class="ui bottom attached green button" onclick="location.href='Googlepay/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Alipay
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryAlipayExample" class="ui bottom attached green button" onclick="location.href='Alipay/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            WeChat Pay
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryWechatPayExample" class="ui bottom attached green button" onclick="location.href='Wechatpay/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Przelewy24 (P24)
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryP24Example" class="ui bottom attached green button" onclick="location.href='Przelewy24/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Prepayment
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryPrePaymentExample" class="ui bottom attached green button" onclick="location.href='Prepayment/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Invoice (deprecated)
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryInvoiceExample" class="ui bottom attached green button" onclick="location.href='Invoice/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Unzer Invoice
                        </div>
                        <div class="description">
                            With "paylater-invoice" type.
                        </div>
                    </div>
                    <div class="ui attached white button" onclick="location.href='https://docs.unzer.com/payment-methods/unzer-invoice-upl/';">
                        Documentation
                    </div>
                    <div id="tryInvoiceSecuredExample" class="ui bottom attached green button" onclick="location.href='PaylaterInvoice/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Unzer Invoice (deprecated)
                        </div>
                        <div class="description">
                            With "invoice-secured" type.
                        </div>
                    </div>
                    <div id="tryInvoiceSecuredExample" class="ui bottom attached green button" onclick="location.href='InvoiceSecured/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Klarna
                        </div>
                    </div>
                    <div id="tryKlarnaExample" class="ui bottom attached green button" onclick="location.href='Klarna/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            PayPal
                        </div>
                        <div class="description">
                            You can try authorize and direct charge.
                        </div>
                    </div>
                    <div id="tryPayPalExample" class="ui bottom attached green button" onclick="location.href='PayPal/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            PayPal Recurring
                        </div>
                        <div class="description">
                            You can set a PayPal type to recurring in order to register it and charge later as well as implement recurring payments.
                        </div>
                    </div>
                    <div id="tryPayPalRecurringExample" class="ui bottom attached green button" onclick="location.href='PayPalRecurring/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            PayU
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryPayUExample" class="ui bottom attached green button" onclick="location.href='PayU/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Sofort
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="trySofortExample" class="ui bottom attached green button" onclick="location.href='Sofort/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Unzer Direct Debit (deprecated)
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryDirectDebitSecuredExample" class="ui bottom attached green button" onclick="location.href='SepaDirectDebitSecured/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Paylater Direct Debit
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryPaylaterDirectDebit" class="ui bottom attached green button" onclick="location.href='PaylaterDirectDebit/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Paylater Installment
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryPaylaterInstallment" class="ui bottom attached green button" onclick="location.href='PaylaterInstallment/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Installment Secured (deprecated)
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryInstallmentSecuredExample" class="ui bottom attached green button" onclick="location.href='InstallmentSecured/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Unzer Bank Transfer (PIS)
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryUnzerBankTransferExample" class="ui bottom attached green button" onclick="location.href='BankTransfer/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Post Finance Card
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryPostFinanceCardExample" class="ui bottom attached green button" onclick="location.href='PostFinanceCard/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Post Finance eFinance
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryPostFinanceEfinanceExample" class="ui bottom attached green button" onclick="location.href='PostFinanceEfinance/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Hosted Payment Page
                        </div>
                        <div class="description">
                            This example shows how to use the Payment Page hosted externally.
                            The customer will be redirected to a Payment Page on a Unzer
                            server and redirected to a given RedirectUrl.
                        </div>
                    </div>
                    <div class="ui attached white button" onclick="location.href='https://docs.unzer.com/online-payments/payment-pages/integrate-hpp/';">
                        Documentation
                    </div>
                    <div id="tryHostedPayPageExample" class="ui bottom attached green button" onclick="location.href='HostedPayPage/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Embedded Payment Page
                        </div>
                        <div class="description">
                            This example shows how to use the embedded Payment Page.
                            The Payment Page will be shown as an Overlay in your own shop.
                        </div>
                    </div>
                    <div class="ui attached white button" onclick="location.href='https://docs.unzer.com/online-payments/payment-pages/integrate-epp/';">
                        Documentation
                    </div>
                    <div id="tryEmbeddedPayPageExample" class="ui bottom attached green button" onclick="location.href='EmbeddedPayPage/';">
                        Try
                    </div>
                </div>
                <div class="card olive">
                    <div class="content">
                        <div class="header">
                            Bancontact
                        </div>
                        <div class="description">
                        </div>
                    </div>
                    <div id="tryBancontactExample" class="ui bottom attached green button" onclick="location.href='Bancontact/';">
                        Try
                    </div>
                </div>
            </div>
        </div>

        <div class="ui container segment">
            <h2 class="ui header">
                <i class="bolt icon"></i>
                <span class="content">
                    Webhook Implementation Examples
                    <span class="sub header">Enable or disable webhooks ...</span>
                </span>
            </h2>
            <div class="ui three cards">
                <div class="card">
                    <div class="content">
                        <div class="header">
                            Register Webhooks
                        </div>
                        <div class="description">
                            Enable a log output in ExampleDebugHandler to see the events coming in.
                        </div>
                    </div>
                    <div class="ui bottom attached blue button" onclick="location.href='Webhooks/';">
                        Try
                    </div>
                </div>
                <div class="card">
                    <div class="content">
                        <div class="header">
                            Delete all Webhooks
                        </div>
                        <div class="description">
                            Delete all Webhooks corresponding to this key pair.
                        </div>
                    </div>
                    <div class="ui bottom attached blue button" onclick="location.href='Webhooks/removeAll.php';">
                        Try
                    </div>
                </div>
                <div class="card">
                    <div class="content">
                        <div class="header">
                            Fetch all Webhooks
                        </div>
                        <div class="description">
                            Fetch all Webhooks corresponding to this key pair.
                        </div>
                    </div>
                    <div class="ui bottom attached blue button" onclick="location.href='Webhooks/fetchAll.php';">
                        Try
                    </div>
                </div>
            </div>
        </div>
    </body>

</html>
