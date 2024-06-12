<?php
/**
 * This is the pending page for the example payments.
 *
 * @link  https://docs.unzer.com/
 *
 */

session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css"/>
</head>
<body style="margin: 30px 70px 0;">
<div class="ui container segment spaced">
    <h1 id="result" class="ui header">Pending</h1>
    <p>
        The payment transaction has been completed, however it has the state pending.<br>
        The status of the payment is not definite at the moment.<br>
        You can create the Order in your shop but should set its status to <i>pending payment</i>.
    </p>
    <p>
        Please use the webhook feature to be informed about later changes of the payment.
        You should ship only if the status changes to success.
        <?php
        if (isset($_SESSION['ShortId']) && !empty($_SESSION['ShortId'])) {
            echo '<p>Please look for ShortId ' . $_SESSION['ShortId'] . ' in Unzer Insights to see the transaction.</p>';
        }
        if (isset($_SESSION['PaymentId']) && !empty($_SESSION['PaymentId'])) {
            echo '<p>The PaymentId of your transaction is \'' . $_SESSION['PaymentId'] . '\'.</p>';
        }
        ?>
    </p>
    <a href="." class="ui green button">start again</a>
</div>
</body>
</html>
