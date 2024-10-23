# UnzerPayment

For a full list of payment methods supported by the Unzer plugin, go to <a href="https://docs.unzer.com/plugins/supported-payment-methods/" target="_blank">Supported payment methods</a>.

For more details about the plugin compatability, go to <a href="https://store.shopware.com/en/unzer48059319318f/unzer-payments-for-shopware-6.html" target="_blank">Shopware store page</a> and the <a href="https://docs.shopware.com/en/shopware-6-en/first-steps/system-requirements" target="_blank">Shopware requirements page</a>.

## Installation

### For production
For detailed list of instructions, go to the <a href="https://docs.unzer.com/plugins/shopware-6/shop6-install-plugin/#step-1-install-the-plugin" target="_blank">Install the plugin</a> section.

### For development
1. Clone the plugin repository into the `custom/plugins` folder in your shopware installation.
2. Inside the plugin directory run `composer install`
3. Go to the plugin manager and install/activate the plugin.
4. Run the following commands inside the Shopware folder:
    > `./psh.phar administration:build`

    > `./psh.phar storefront:build`

This automatically generates all the files required for the plugin to work correctly.

## Configuration

After the plugin installation, it is necessary to add the new payment methods to the desired sales channel.
Currently, the only sales channel that is supported is the Storefront.

For more information about configuration, go to the <a href="https://docs.unzer.com/plugins/shopware-6/" target="_blank">Unzer Shopware 6 documentation</a>.
