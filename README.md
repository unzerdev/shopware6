# HeidelPayment

Heidelpay payment integration for Shopware 6 including the following payment methods:
* Credit Card
* Invoice
* SOFORT

# Installation
1. Clone the plugin repository into the `custom/plugins` folder in your shopware installation.
2. Inside the plugin directory run `composer install --no-dev`.
3. Go to the plugin manager and install/activate the plugin.

# Troubleshooting

* *JavaScript does not load correctly in the storefront*

In this case it's required to run the command `./psh.phar storefront:build` manually in the shopware directory

* *I can not see the Heidelpay-Tab inside the order details*

In this case it's required to run the command `./psh.phar administration:build` manually in the shopware directory
