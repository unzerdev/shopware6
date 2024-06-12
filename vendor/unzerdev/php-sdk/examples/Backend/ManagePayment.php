<?php
/**
 * This is a Backend page where a merchant can perform charge and cancel transactions for the current payment. This is only available for "paylater-invoice" and "klarna" type right now.
 *
 * @link  https://docs.unzer.com/
 *
 */

use UnzerSDK\Resources\PaymentTypes\Paypal;

require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ . '/../Constants.php';

session_start();

$additionalPaymentInformation = $_SESSION['additionalPaymentInformation'] ?? null;
$shortId = $_SESSION['ShortId'] ?? null;
$paymentId = $_SESSION['PaymentId'] ?? null;
$paymentTypeId = $_SESSION['PaymentTypeId'] ?? null;
$isAuthorizeTransaction = $_SESSION['isAuthorizeTransaction'] ?? false;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unzer UI Examples</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css"/>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css"/>

    <link rel="stylesheet" href="https://static.unzer.com/v1/unzer.css"/>
</head>
<body style="margin: 70px 70px 0;">
<div class="ui container segment">
    <h1 id="result" class="ui header">Manage Payment (Merchant only)</h1>
    <div class="ui content">
        <?php
        if (!empty($additionalPaymentInformation)) {
            echo '<h3>' . $additionalPaymentInformation . '</h3>';
        }

        $paymentId = $_SESSION['PaymentId'] ?? null;
        if ($paymentId !== null) {
            echo '<p>The PaymentId of your transaction is \'' . $paymentId . '\'.</p>';
        }

        $unzer = new \UnzerSDK\Unzer(UNZER_PAPI_PRIVATE_KEY);
        $payment = $unzer->fetchPayment($paymentId);
        if ($shortId !== null) {
            $defaultTransactionMessage = '<p>Please look for ShortId ' . $shortId . ' in Unzer Insights to see the transaction.</p>';
            $paylaterTransactionMessage = '<p>Please use the "descriptor" to look for the transaction in the Unzer Pay Later Merchant Portal.</p>';
            echo preg_match('/[\d]{4}.[\d]{4}.[\d]{4}/', $shortId) ? $defaultTransactionMessage : $paylaterTransactionMessage;
        }

        if ($payment->getAmount()->getRemaining() > 0 && $payment->getAuthorization() !== null) {
            echo '<h2>Charge payment</h2>
                <p>You can use the payment ID to charge the payment.</p>
                        <form id="payment-form" class="unzerUI form" action="' . CHARGE_PAYMENT_CONTROLLER_URL . '" method="post">
                            <input type="hidden" name="payment_id" value="' . $paymentId . ' ">
                            <div class="fields inline">
                                <div class="field">
                                    <button class="unzerUI primary button fluid" id="submit-button" type="submit">Capture payment</button>
                                </div>
                            </div>
                        </form><br>';
        }

        if ($payment->getPaymentType()->supportsDirectPaymentCancel() && !$payment->isCanceled()) {
            echo '<h2>Cancel payment.</h2>
                        <p>You can use the payment ID to cancel the payment.</p>
                        <form id="payment-form" class="unzerUI form" action="' . CANCEL_PAYMENT_CONTROLLER_URL . '" method="post">
                            <input type="hidden" name="payment_id" value="' . $paymentId . ' ">
                            <div class="fields inline">
                                <div class="field">
                                    <button class="unzerUI primary button fluid" id="submit-button" type="submit">Cancel payment</button>
                                </div>
                            </div>
                        </form><br>';
        }

        if ($payment->getPaymentType() instanceof Paypal) {
            echo '<h2>PayPal Express only: Finalize a transaction</h2>
                        <p>You can finalize a transaction in resumed state.</p>
                        <form id="payment-form" class="unzerUI form" action="' . UPDATE_TRANSACTION_CONTROLLER_URL . '" method="post">
                            <input type="hidden" name="payment_id" value="' . $paymentId . ' ">
                            <label for="shiipping_amount">Shipping amount: </label>
                            <input type="number" name="shipping_amount" value="0" step="any">
                            <div class="fields inline">
                                <div class="field">
                                    <button class="unzerUI primary button fluid" id="submit-button" type="submit">Finalize Transaction</button>
                                </div>
                            </div>
                        </form><br>';
        }

        ?>
        <a href=".." class="ui green button">start again</a>
</div>
</body>
</html>
