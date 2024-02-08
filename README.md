# UnzerPayment

Unzer payment integration for Shopware 6 including the following payment methods:
* Alipay
* Bancontact
* Credit Card
* EPS
* Giropay
* iDEAL
* Invoice
* PayPal
* Prepayment
* SEPA direct debit
* SOFORT
* Unzer Direct
* Unzer direct debit (secured)
* Unzer Invoice B2C / B2B (secured)
* Unzer Installment (secured)
* WeChat Pay

Regarding plugin compatibility, please take a look at the [Shopware store page](https://store.shopware.com/en/unzer48059319318f/unzer-payments-for-shopware-6.html).

## Installation
### For production
1. Upload the plugin files into the `custom/plugins` folder in your shopware installation.
2. Inside the plugin directory `custom/plugins/UnzerPayment6` run `composer install --no-dev`
3. Switch to admin and install the plugin using the Shopware plugin manager and configure it as you need.

### For development
1. Clone the plugin repository into the `custom/plugins` folder in your shopware installation.
2. Inside the plugin directory run `composer install`
3. Go to the plugin manager and install/activate the plugin.
4. Run the following commands inside the Shopware folder:
    > `./psh.phar administration:build`

    > `./psh.phar storefront:build`

This will automatically generate all files required for the plugin to work correctly

## Configuration
After the actual plugin installation it is necessary to add the new payment methods to the desired sales channel. 
Currently, the only sales channel that is supported is the Storefront.

Further information and configuration you can find within the <a href="https://docs.unzer.com/plugins/shopware-6/" target="_blank">manual</a>
