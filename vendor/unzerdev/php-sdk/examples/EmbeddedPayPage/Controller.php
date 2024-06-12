<?php
/**
 * This is the controller for the  Embedded Payment Page example.
 * It is called when the pay button on the index page is clicked.
 *
 * @link  https://docs.unzer.com/
 *
 */

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** @noinspection PhpIncludeInspection */
/** Require the composer autoloader file */
require_once __DIR__ . '/../../../../autoload.php';

use UnzerSDK\Constants\CustomerGroups;
use UnzerSDK\Constants\CustomerRegistrationLevel;
use UnzerSDK\Constants\Salutations;
use UnzerSDK\examples\ExampleDebugHandler;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\Address;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use UnzerSDK\Resources\PaymentTypes\Paypage;
use UnzerSDK\Unzer;

// start new session for this example and remove all parameters
session_start();
session_unset();

header('Content-Type: application/json');

$clientMessage = 'Something went wrong. Please try again later.';
$merchantMessage = 'Something went wrong. Please try again later.';

// These lines are just for this example
$transactionType = $_POST['transaction_type'] ?? 'authorize';

// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create an Unzer object using your private key and register a debug handler if you want to.
    $unzer = new Unzer(UNZER_PAPI_PRIVATE_KEY);
    $unzer->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

    // A customer with matching addresses is mandatory for Installment payment type
    $address = (new Address())
        ->setName('Max Mustermann')
        ->setStreet('Vangerowstr. 18')
        ->setCity('Heidelberg')
        ->setZip('69155')
        ->setCountry('DE');

    // Create a charge/authorize transaction
    $customer = CustomerFactory::createCustomer('Max', 'Mustermann')
        ->setSalutation(Salutations::MR)
        ->setBirthDate('2000-02-12')
        ->setLanguage('de')
        ->setEmail('test@test.com');

    // These are the mandatory parameters for the payment page ...
    $paypage = new Paypage(119.00, 'EUR', RETURN_CONTROLLER_URL);
    $orderId = 'o' . str_replace(['0.', ' '], '', microtime(false));

    // Just for example purpose. Make sure to generate a unique ID.
    $threatMetrixId = 'php-sdk-example_' . $orderId;

    // ... however you can customize the Payment Page using additional parameters.
    $paypage->setShopName('My Test Shop')
        ->setTagline('Try and stop us from being awesome!')
        ->setTermsAndConditionUrl('https://www.unzer.com/en/')
        ->setPrivacyPolicyUrl('https://www.unzer.com/de/datenschutz/')
        ->setOrderId($orderId)
        ->setLogoImage(UNZER_PP_LOGO_URL)
        ->setAdditionalAttribute('riskData.threatMetrixId', $threatMetrixId)
        ->setAdditionalAttribute('riskData.customerGroup', CustomerGroups::GOOD)
        ->setAdditionalAttribute('riskData.confirmedAmount', 99.99)
        ->setAdditionalAttribute('riskData.confirmedOrders', 2)
        ->setAdditionalAttribute('riskData.registrationLevel', CustomerRegistrationLevel::REGISTERED)
        ->setAdditionalAttribute('riskData.registrationDate	', '20160412')
        ->setInvoiceId('i' . microtime(true));

    // ... in order to enable Unzer Instalment you will need to set the effectiveInterestRate as well.
    $paypage->setEffectiveInterestRate(4.99);

    // ... a Basket is mandatory for InstallmentSecured
    $basketItem = (new BasketItem())
        ->setAmountPerUnitGross(119.00)
        ->setVat(19.00)
        ->setQuantity(1)
        ->setBasketItemReferenceId('item1')
        ->setTitle('Hat');

    $basket = new Basket($orderId);
    $basket->setTotalValueGross(119.00)
        ->addBasketItem($basketItem)
        ->setCurrencyCode('EUR');

    if ($transactionType === 'charge') {
        $unzer->initPayPageCharge($paypage, $customer, $basket);
    } else {
        // For demo purpose we set customer address for authorize only to enable instalment payment.
        // That way e.g. Invoice secured will display customer form on payment page.
        $customer
            ->setShippingAddress($address)
            ->setBillingAddress($address);
        $unzer->initPayPageAuthorize($paypage, $customer, $basket);
    }

    $_SESSION['PaymentId'] = $paypage->getPaymentId();
    echo json_encode(['token' => $paypage->getId()]);
    die();

} catch (UnzerApiException $e) {
    $merchantMessage = $e->getMerchantMessage();
    $clientMessage = $e->getClientMessage();
} catch (RuntimeException $e) {
    $merchantMessage = $e->getMessage();
}

http_response_code(400);
echo json_encode(['merchant' => $merchantMessage, 'customer' => $clientMessage]);
