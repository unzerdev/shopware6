# Unzer Payment plugin for Shopware 6

Use Unzer Payment plugin for Shopware 6 to provide an easy-to-install payment gateway integration for all your online payments.

## Description

Accept payments with cards, bank transfers, wallets, and other global and local payment methods. Unzer Payment plugin helps you with quick and easy integration, full support, and flexible solutions that grow with your business. We are your payment partner for every situation.

## Features

* Seamless integration into the Shop-system
* Merchant-friendly order management with up-to-date payment details, real-time billing and refunds made easy.
* Payment processing via the Unzer Payment API
* 3D-Secure authentication
* PCI-DSS Level 1 certified

## Content security policy (CSP)

If you are using a Content Security Policy (CSP) you must include different Unzer URL's to your policy, which are required by the UI components to work. For more information, please go to [Unzer Documentation CSP Information](https://docs.unzer.com/online-payments/ui-component-v2/#content-security-policy-csp).

## Supported payment methods

Unzer payment integration for Shopware 6 includes the following payment methods:
* Alipay
* Apple Pay
* Bancontact
* Credit Card and Click to Pay
* Unzer Direct Bank Transfer
* EPS
* Google Pay
* iDEAL
* PayPal
* Prepayment
* SEPA Direct Debit
* TWINT
* Unzer Direct Debit
* Unzer Direct Debit (secured)
* Unzer Invoice B2C / B2B (secured)
* Unzer Installment (secured)
* WeChat Pay
* Wero

Regarding plugin compatibility, please take a look at the release notes for more information.

## Updating

Version 5.12.0 is a breaking change - please see https://docs.unzer.com/plugins/shopware-6/shop6-migrate-v1-v2/

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

## User Guide

Please find information on installation, configuration, usage etc on our [documentation pages](https://docs.unzer.com/plugins/shopware-6).

## Support

For any issues or questions please get in touch with our support.

**Email**: support@unzer.com

**Phone**: +49 (0)6221/6471-100

**Twitter**: [@UnzerTech](https://twitter.com/UnzerTech)

**Webpage**: https://unzer.com/
