<?php
/**
 * This file defines the constants needed for the card example.
 *
 * @link  https://docs.unzer.com/
 *
 */

require_once __DIR__ . '/_enableExamples.php';
if (defined('UNZER_PAPI_EXAMPLES') && UNZER_PAPI_EXAMPLES !== true) {
    exit();
}

const EXAMPLE_BASE_FOLDER = UNZER_PAPI_URL . UNZER_PAPI_FOLDER;
define('SUCCESS_URL', EXAMPLE_BASE_FOLDER . 'Success.php');
define('CREATE_URL', EXAMPLE_BASE_FOLDER . 'Create.php');
define('PENDING_URL', EXAMPLE_BASE_FOLDER . 'Pending.php');
define('FAILURE_URL', EXAMPLE_BASE_FOLDER . 'Failure.php');
define('RETURN_CONTROLLER_URL', EXAMPLE_BASE_FOLDER . 'ReturnController.php');
define('BACKEND_URL', EXAMPLE_BASE_FOLDER . 'Backend/ManagePayment.php');
define('BACKEND_FAILURE_URL', EXAMPLE_BASE_FOLDER . 'Backend/Failure.php');
define('RECURRING_PAYMENT_CONTROLLER_URL', EXAMPLE_BASE_FOLDER . 'CardRecurring/RecurringPaymentController.php');
define('CHARGE_PAYMENT_CONTROLLER_URL', EXAMPLE_BASE_FOLDER . 'Backend/ChargePaymentController.php');
define('CANCEL_PAYMENT_CONTROLLER_URL', EXAMPLE_BASE_FOLDER . 'Backend/CancelPaymentController.php');
define('UPDATE_TRANSACTION_CONTROLLER_URL', EXAMPLE_BASE_FOLDER . 'Backend/UpdateTransactionController.php');
