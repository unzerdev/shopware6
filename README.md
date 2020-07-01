# HeidelPayment

Heidelpay payment integration for Shopware 6.2 including the following payment methods:
* Credit Card
* SEPA direct debit
* Flexipay direct debit (secured)
* Prepayment
* Invoice
* FlexiPay® Invoice B2C / B2B (secured, factoring)
* FlexiPay® Direct
* SOFORT
* giropay
* EPS
* iDEAL
* PayPal
* Alipay
* WeChat Pay


## Installation
### For production
1. Upload the plugin files into the `custom/plugins` folder in your shopware installation.
2. Inside the plugin directory `custom/plugins/HeidelPayment6` run `composer install --no-dev`
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
Currently the only sales channel that is supported is the Storefront.

Further information and configuration you can find within the manual at the <a href="https://dev.heidelpay.de/handbuch-shopware-ab-6-2-version-0-0-1/" target="_blank">manual</a>

## Troubleshooting

#### Known Issues
> This plugin is a beta-release. For all known issues please have a look at the <a href="https://dev.heidelpay.de/handbuch-shopware-ab-6-2-version-0-0-1/#Known_issues" target="_blank">documentation</a>

#### JavaScript does not load correctly in the storefront

> In this case it's required to run the command `./psh.phar storefront:build` manually in the shopware directory

#### I can not see the Heidelpay-Tab inside the order details

>In this case it's required to run the command `./psh.phar administration:build` manually in the shopware directory

#### I do not see the plugin inside the plugin list
>In this case it's required to run the command `./bin/console plugin:refresh` manually in the shopware directory

#### Support
If you need support please feel free to contact us via e-mail <a href="mailto:support@heidelpay.com">support@heidelpay.com</a>