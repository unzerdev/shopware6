<?php
/**
 * For security reasons all examples are disabled by default
 * You can switch the constant 'UNZER_PAPI_EXAMPLES' to true to make the examples executable.
 * But you should always set it false on productive environments.
 *
 * @link  https://docs.unzer.com/
 *
 */

/* Set to true if you want to enable the examples */
define('UNZER_PAPI_EXAMPLES', false);

/* Please set this to your url. It must be reachable over the net
Webhooks will work with https only. However, protocol can be changed to http if necessary. */
define('UNZER_PAPI_URL', 'https://'.$_SERVER['HTTP_HOST']);

/* Please enter the path from root directory to the example folder */
define('UNZER_PAPI_FOLDER', '/vendor/unzerdev/php-sdk/examples/');

/* Please provide your own sandbox-keypair here. */
define('UNZER_PAPI_PRIVATE_KEY', 's-priv-***');
define('UNZER_PAPI_PUBLIC_KEY', 's-pub-***');

/* Image URLs used for Paymentpages can be adjusted here */
define('UNZER_PP_LOGO_URL', 'https://sbx-insights.unzer.com/static/unzerLogo.svg');
define('UNZER_PP_FULL_PAGE_IMAGE_URL', 'https://raw.githubusercontent.com/unzerdev/php-sdk/da9c3fce11264f412e03009606621cc6d9ec0ab1/unzer_logo.svg');

/* ============ PaymentType Specific settings ============ */

/* ------------ Google Pay ------------ */
// Channel of 'googlepay' type. You can find the channel in your keypair config.
define('UNZER_EXAMPLE_GOOGLEPAY_CHANNEL', '');

/* ------------ Apple Pay ------------ */
/* For Apple Pay only, set the path to your Apple Pay Merchant-ID certificate. */
define('UNZER_EXAMPLE_APPLEPAY_MERCHANT_CERT', UNZER_PAPI_FOLDER . '');

/* Set the path to the key file of your Apple Pay Merchant-ID certificate. */
define('UNZER_EXAMPLE_APPLEPAY_MERCHANT_CERT_KEY', UNZER_PAPI_FOLDER .'');

/* Merchant identifier of Apple Pay Merchant ID. */
define('UNZER_EXAMPLE_APPLEPAY_MERCHANT_IDENTIFIER', '');
