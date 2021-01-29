# UnzerPayment

Unzer payment integration for Shopware 6 including the following payment methods:
* Alipay
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

The plugin is compatible with Shopware versions **6.2.X** to **6.3.4.X**

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
Currently the only sales channel that is supported is the Storefront.

Further information and configuration you can find within the <a href="https://dev.unzer.de/handbuch-shopware-ab-6-2-version-0-0-1/" target="_blank">manual</a>

## Troubleshooting

#### Known Issues
> This plugin is a beta-release. For all known issues please have a look at the <a href="https://dev.unzer.de/handbuch-shopware-ab-6-2-version-0-0-1/#Known_issues" target="_blank">documentation</a>

#### JavaScript does not load correctly in the storefront

> In this case it's required to run the command `./psh.phar storefront:build` manually in the shopware directory

#### I can not see the Unzer-Tab inside the order details

>In this case it's required to run the command `./psh.phar administration:build` manually in the shopware directory

#### I do not see the plugin inside the plugin list
>In this case it's required to run the command `./bin/console plugin:refresh` manually in the shopware directory

#### Support
If you need support please feel free to contact us via e-mail <a href="mailto:support@unzer.com">support@unzer.com</a>
