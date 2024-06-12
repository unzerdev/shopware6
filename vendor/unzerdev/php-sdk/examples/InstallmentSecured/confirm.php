<?php
/**
 * This file provides an example implementation of the Installment Secured payment type.
 * It shows the selected payment plan to the customer who can approve the plan to perform the payment.
 *
 * @link  https://docs.unzer.com/
 *
 */

use UnzerSDK\examples\ExampleDebugHandler;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Unzer;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** @noinspection PhpIncludeInspection */
/** Require the composer autoloader file */
require_once __DIR__ . '/../../../../autoload.php';

session_start();

$clientMessage = 'Something went wrong. Please try again later.';
$merchantMessage = 'Something went wrong. Please try again later.';

function redirect($url, $merchantMessage = '', $clientMessage = '')
{
    $_SESSION['merchantMessage'] = $merchantMessage;
    $_SESSION['clientMessage']   = $clientMessage;
    header('Location: ' . $url);
    die();
}

$paymentId = $_SESSION['PaymentId'] ?? null;
if ($paymentId === null) {
    redirect(FAILURE_URL, 'Payment id is missing!', $clientMessage);
}

// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create an Unzer object using your private key and register a debug handler if you want to.
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

    $payment = $unzer->fetchPayment($paymentId);

    $PDFLink = $payment->getAuthorization()->getPDFLink();
    /** @var InstallmentSecured $type */
    $type = $payment->getPaymentType();
    $totalAmount = $type->getTotalAmount();
    $totalPurchaseAmount = $type->getTotalPurchaseAmount();
    $totalInterestAmount = $type->getTotalInterestAmount();
    $currency = $payment->getAmount()->getCurrency();
} catch (UnzerApiException $e) {
    $merchantMessage = $e->getMerchantMessage();
    $clientMessage = $e->getClientMessage();
    redirect(FAILURE_URL, $merchantMessage, $clientMessage);
} catch (RuntimeException $e) {
    $merchantMessage = $e->getMessage();
    redirect(FAILURE_URL, $merchantMessage, $clientMessage);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unzer UI Examples</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
            crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css" />

</head>

<body style="margin: 70px 70px 0;">

<div class="ui container segment">
    <h2 class="ui header">
        <i class="search icon"></i>
        <span class="content">
            Confirm instalment plan
            <span class="sub header">Download the instalment plant information and confirm your order...</span>
        </span>
    </h2>
</div>

<div class="ui container">
    <div class="ui attached right aligned segment">
        <div class="ui label">
            Total Purchase Amount
            <div id="total_purchase_amount" class="detail"><?php echo number_format($totalPurchaseAmount, 2) . ' ' . $currency; ?></div>
        </div>
        <div class="ui hidden fitted divider"></div>
        <div class="ui label">
            Total Interest Amount
            <div id="total_interest_amount" class="detail"><?php echo number_format($totalInterestAmount, 2) . ' ' . $currency; ?></div>
        </div>
        <div class="ui hidden fitted divider"></div>
        <div class="ui label">
            Total Amount
            <div id="total_amount" class="detail"><?php echo number_format($totalAmount, 2) . ' ' . $currency; ?></div>
        </div>
    </div>
    <div class="ui attached segment">
        <strong>Please download your rate plan <a href="<?php echo (string)($PDFLink); ?>">here</a></strong><br/>
    </div>
    <div id="place_order" class="ui bottom attached primary button" tabindex="0" onclick="location.href='PlaceOrderController.php'">Place order</div>
</div>

</body>
</html>
