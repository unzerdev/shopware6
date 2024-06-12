<?php

/*
 *  Controller for charge on payment (capture).
 *
 *  @link  https://docs.unzer.com/
 *
 */

/** Require the constants of this example */
require_once __DIR__ . '/../Constants.php';

/** Require the composer autoloader file */
/** @noinspection PhpIncludeInspection */
require_once __DIR__ . '/../../../../autoload.php';

use UnzerSDK\examples\ExampleDebugHandler;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Unzer;

session_start();

$clientMessage = 'Something went wrong. Please try again later.';
$merchantMessage = 'Something went wrong. Please try again later.';
$debugHandler = new ExampleDebugHandler();

function redirect($url, $merchantMessage = '', $clientMessage = '')
{
    $_SESSION['merchantMessage'] = $merchantMessage;
    $_SESSION['clientMessage']   = $clientMessage;
    header('Location: ' . $url);
    die();
}

// You will need the id of the payment type created in the frontend (index.php)
if (!isset($_POST['payment_id'])) {
    redirect(FAILURE_URL, 'Resource id is missing!', $clientMessage);
}
$paymentId   = $_POST['payment_id'];
$shippingCost   = $_POST['shipping_amount'];

// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create an Unzer object using your private key and register a debug handler if you want to.
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler($debugHandler);

    $payment = $unzer->fetchPayment($paymentId);
    $transaction = $payment->getInitialTransaction();

    if (!$transaction->isResumed()) {
        redirect(BACKEND_FAILURE_URL, 'Transaction can only be updated in resumed state.');
    }

    //Add Shipping cost to initial amount.
    $updatedCost = $transaction->getAmount() + $shippingCost;

    // Update transaction amount.
    $transaction->setAmount($updatedCost);

    //Update basket
    $basket = $payment->getBasket();

    if ($basket !== null && $shippingCost > 0) {
        $shippingItem = new BasketItem('shipping costs');
        $shippingItem->setAmountPerUnitGross($shippingCost);
        $basket->addBasketItem($shippingItem);
        $basket->setTotalValueGross($updatedCost);
        $unzer->updateBasket($basket);
    }

    if ($transaction instanceof Authorization) {
        $unzer->updateAuthorization($transaction->getPaymentId(), $transaction);
    } else {
        /** @var $transaction Charge */
        $unzer->updateCharge($transaction->getPaymentId(), $transaction);
    }

    // You'll need to remember the paymentId for later in the ReturnController (in case of 3ds)
    $_SESSION['ShortId'] = $transaction->getShortId();
    unset($_SESSION['isAuthorizeTransaction']);

    // Redirect to the failure page or to success depending on the state of the transaction
    $redirect = !empty($transaction->getRedirectUrl());
    if (!$redirect && $transaction->isSuccess()) {
        $_SESSION['additionalPaymentInformation'] = '<p>Updating(PATCH) transaction was successful.</p>';
        redirect(BACKEND_URL);
    } elseif ($redirect && $transaction->isPending()) {
        redirect(BACKEND_FAILURE_URL, 'Transaction initiated by merchant should not redirect to 3ds Page. The customer needs to
        do the 3ds authentication first for that payment type.');
    }

    // Check the result message of the transaction to find out what went wrong.
    $merchantMessage = $transaction->getMessage()->getCustomer();
} catch (UnzerApiException $e) {
    $merchantMessage = $e->getMerchantMessage();
    $clientMessage = $e->getClientMessage();
} catch (RuntimeException $e) {
    $merchantMessage = $e->getMessage();
}
redirect(BACKEND_FAILURE_URL, $merchantMessage, $clientMessage);
