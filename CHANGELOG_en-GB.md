# 3.0.1
* Fix redirect in case of an error when deleting a payment device
* Fix display of the devices for SEPA secured
* Updated Unzer PHP SDK to version 1.1.4.0
* Add compatibility to PHP 8

# 3.0.0
* Add Administration UI for refund reason codes
* Add additional routes for passing reason codes
* Change Cancel Order Interface to pass reason code
* Added Bancontact as a new payment method
* Fixed payment with installment and discounts
* Fixed an error that occured when switching the shipping address
* Customers are now being updated in the Unzer Insight Board
* Fixed backwards compatibility to Shopware 6.3 and lower for SEPA payment methods
* Fixed the maximum birthday year
* Fixed the order list override so other plugins are also able to manipulate the list
* Compatibility with shopware 6.4.3.0 ensured
* Correction of the webhook registration for multiple sales channels with different credentials
* Fixed an error that saved PayPal accounts although no email was provided by the API

# 2.0.1
* Compatibility with shopware 6.4.0.0 ensured
* Fixed SEPA-Mandate text in checkout

# 2.0.0
* Added birthdate validation to payment method Unzer installment in checkout
* Switch to the new unzer SDK (https://packagist.org/packages/unzerdev/php-sdk)
* Error in plugin configuration for inherited settings corrected

# 1.0.4
* Error in Invoice (guaranteed/factoring) for B2B customers corrected
* Error in the decimal place display in the administration fixed
* Correction of the missing display of the total amount in the checkout
* Correction of an error in the administration when editing an order.
* Compatibility with shopware 6.3.5.1 ensured

# 1.0.3
* Adjustments of the code style and increase of the code quality
* Correction of missing decimal places Display in admin for reimbursement and collection
* Correction of missing labels in the plugin settings
* Correction of webhook registration
* Payment methods for shopping carts with a value of zero are now disabled

# 1.0.2
* Correction of the voucher handling

# 1.0.1
* Correction of payment status changes

# 1.0.0
* Release
