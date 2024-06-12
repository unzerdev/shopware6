<?php
/**
 * This is the success page for the example payments.
 *
 * @link  https://docs.unzer.com/
 *
 */

require_once __DIR__ . '/Constants.php';

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

        <link rel="stylesheet" href="https://static.unzer.com/v1/unzer.css" />
    </head>
    <body>
        <h1 id="result">Create</h1>
        <p>
            The payment is in create state, meaning the Payment page was not used for a payment yet.<br>
            The payment also stays in this state when the customer clicked the "Back to merchant" button or closed the browser tab.<br>
            It is still active and could be used by the customer for the current payment.<br>
        <?php
        echo  'Paypage-URL: <a href="' . $_SESSION['paypageId'] . '">Payment page</a>';

        if (!empty($additionalPaymentInformation)) {
            echo $additionalPaymentInformation;
        }

        if ($shortId !== null) {
            $defaultTransactionMessage = '<p>Please look for ShortId ' . $shortId . ' in Unzer Insights to see the transaction.</p>';
            $paylaterTransactionMessage = '<p>Please use the "descriptor" to look for the transaction in the Unzer Pay Later Merchant Portal.</p>';
            echo preg_match('/[\d]{4}.[\d]{4}.[\d]{4}/', $shortId) ? $defaultTransactionMessage : $paylaterTransactionMessage;
        }
        $paymentId = $_SESSION['PaymentId'] ?? null;
        if ($paymentId !== null) {
            echo '<p>The PaymentId is \'' . $paymentId . '\'.</p>';
        }

        if ($paymentTypeId !== null) {
                echo    '<p>The TypeId for the recurring payment is \'' . $paymentTypeId . '\'. You can use it
                            now for subsequent transactions.</p>
                            <form id="payment-form" class="unzerUI form" action="' . RECURRING_PAYMENT_CONTROLLER_URL . '" method="post">
                                <input type="hidden" name="payment_type_id" value="' . $paymentTypeId . ' ">
                                <div class="fields inline">
                                    <div class="field">
                                        <button class="unzerUI primary button fluid" id="submit-button" type="submit">Charge payment type again.</button>
                                    </div>
                                </div>
                            </form>';
            }
        ?>
        </p>
        <p><a href=".">start again</a></p>
    </body>
</html>
