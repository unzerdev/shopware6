<?php
/** @noinspection MissingOrEmptyGroupStatementInspection */
/** @noinspection PhpStatementHasEmptyBodyInspection */
/**
 * This is the return controller for example implementations.
 * It is called when the client is redirected back to the shop from the external page or when the payment
 * transaction has been sent.
 *
 * @link  https://docs.unzer.com/
 *
 */

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** @noinspection PhpIncludeInspection */
/** Require the composer autoloader file */
require_once __DIR__ . '/../../../autoload.php';

use UnzerSDK\examples\ExampleDebugHandler;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Unzer;
use UnzerSDK\Resources\PaymentTypes\Prepayment;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;

$clientMessage = 'Something went wrong. Please try again later.';
$merchantMessage = 'Something went wrong. Please try again later.';

function redirect($url, $merchantMessage = '', $clientMessage = '')
{
    $_SESSION['merchantMessage'] = $merchantMessage;
    $_SESSION['clientMessage']   = $clientMessage;
    header('Location: ' . $url);
    die();
}

session_start();

// Retrieve the paymentId you remembered within the Controller
if (!isset($_SESSION['PaymentId'])) {
    $merchantMessage = 'The payment id is missing.';
}
$paymentId = $_SESSION['PaymentId'];

$redirectUrl = FAILURE_URL;
// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create an Unzer object using your private key and register a debug handler if you want to.
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

    // Redirect to success if the payment has been successfully completed.
    $payment   = $unzer->fetchPayment($paymentId);
    $transaction = $payment->getInitialTransaction();

    // Ensure that shortId is also set in case of payment pages.
    if ($transaction !== null) {
        $_SESSION['ShortId'] = $_SESSION['ShortId'] ?? $transaction->getShortId();
    }

    if ($payment->isCompleted()) {
        // The payment process has been successful.
        // You show the success page.
        // Goods can be shipped.
        $redirectUrl = SUCCESS_URL;
    }

    if ($payment->isCreate()) {
        // The payment is in create state, meaning the customer clicked the "Back to Merchant" button.
        // The Payment page was not used for a payment yet.
        // It is still active and could be used by the customer for the current payment.
        $_SESSION['paypageId'] = $payment->getPayPage()->getRedirectUrl();
        $redirectUrl = CREATE_URL;
    }

    if ($payment->isPending()) {
        if ($transaction->isSuccess() || $transaction->isResumed()) {
            if ($transaction instanceof Authorization) {
                // Payment is ready to be captured.
                // Goods can be shipped later AFTER charge.
            } else {
                // Payment is not done yet (e.g. Prepayment)
                // Goods can be shipped later after incoming payment (event).
            }

            // In any case:
            // * You can show the success page.
            // * You can set order status to pending payment
            $redirectUrl = SUCCESS_URL;
        } elseif ($transaction->isPending()) {
            // In cases of a redirect to an external service (e.g. 3D secure, PayPal, etc) it sometimes takes time for
            // the payment to update it's status after redirect into shop.
            // In this case the payment and the transaction are pending at first and change to cancel or success later.

            // Use the webhooks feature to stay informed about changes of payment and transaction (e.g. cancel, success)
            // then you can handle the states as shown above in transaction->isSuccess() branch.
            $redirectUrl = PENDING_URL;

            // The initial transaction of invoice types will not change to success but stay pending.
            $paymentType = $payment->getPaymentType();
            if ($paymentType instanceof Prepayment || $paymentType->isInvoiceType()) {
                // Awaiting payment by the customer.
                // Goods can be shipped immediately except for Prepayment type.
                $redirectUrl = SUCCESS_URL;
            }
        }
    }

    // If the payment is neither success nor pending something went wrong.
    // In this case do not create the order or cancel it if you already did.
    // Redirect to an error page in your shop and show a message if you want.

    // Check the result message of the initial transaction to find out what went wrong.
    if ($transaction instanceof AbstractTransactionType) {
        // For better debugging log the error message in your error log
        $merchantMessage = $transaction->getMessage()->getMerchant();
        $clientMessage = $transaction->getMessage()->getCustomer();
    }
} catch (UnzerApiException $e) {
    // Write the merchant message to your log.
    // Show the client message to the customer (it is localized).
    $merchantMessage = $e->getMerchantMessage();
    $clientMessage = $e->getClientMessage();
} catch (RuntimeException $e) {
    $merchantMessage = $e->getMessage();
}
redirect($redirectUrl, $merchantMessage, $clientMessage);
