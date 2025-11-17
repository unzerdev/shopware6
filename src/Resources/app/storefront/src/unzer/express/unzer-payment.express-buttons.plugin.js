const Plugin = window.PluginBaseClass;

export default class UnzerPaymentExpressButtonsPlugin extends Plugin {
    static options = {
        googlePay: {},
        applePay: {},
    };

    /**
     * @type {Boolean}
     */

    init() {
        this.includeJs();
        this.registerActions();
    }

    includeJs() {
        if (
            !document.querySelector(
                'script[src*="static-v2.unzer.com/v2/ui-components/index.js"]'
            )
        ) {
            const script = document.createElement('script');
            script.type = 'module';
            script.src =
                'https://static-v2.unzer.com/v2/ui-components/index.js';
            document.head.appendChild(script);
        }
    }

    registerActions() {
        Promise.all([
            customElements.whenDefined('unzer-payment'),
            customElements.whenDefined('unzer-google-pay'),
            customElements.whenDefined('unzer-paypal-express'),
            customElements.whenDefined('unzer-apple-pay'),
        ]).then(() => {
            const unzerExpressPayment = this.el.querySelector(
                '.unzer-express-payment'
            );
            const unzerPaypalExpress = this.el.querySelector(
                '.unzer-paypal-express'
            );
            const unzerGooglePay = this.el.querySelector('.unzer-google-pay');
            const unzerApplePay = this.el.querySelector('.unzer-apple-pay');

            if (!unzerExpressPayment) {
                return;
            }

            if (unzerPaypalExpress) {
                this.registerPaypalExpress(
                    unzerPaypalExpress,
                    unzerExpressPayment
                );
            }

            if (unzerGooglePay) {
                this.registerGooglePay(unzerGooglePay, unzerExpressPayment);
            }

            if (unzerApplePay) {
                if (
                    window.ApplePaySession &&
                    window.ApplePaySession.canMakePayments() &&
                    window.ApplePaySession.supportsVersion(6)
                ) {
                    this.registerApplePay(unzerApplePay, unzerExpressPayment);
                } else {
                    document.querySelector(
                        '.unzer-applepay-express-container'
                    ).style.display = 'none';
                }
            }
        });
    }

    registerPaypalExpress(paypalButton, unzerExpressPayment) {
        paypalButton.id =
            'unzer-paypal-button-' + Math.floor(Math.random() * 10000);
        paypalButton.addEventListener('click', async (event) => {
            event.stopPropagation();
            const response = await unzerExpressPayment.submit();
            if (response.submitResponse && response.submitResponse.success) {
                const paymentTypeId = response.submitResponse.data.id;
                fetch('/unzer/paypal-express', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        paymentTypeId: paymentTypeId,
                    }),
                })
                    .then((response) => response.json())
                    .then((json) => {
                        location.href = json.redirectUrl;
                    });
            } else {
                /* Handle resource creation error */
            }
        });
    }

    registerGooglePay(googlePayButton, unzerExpressPayment) {
        unzerExpressPayment.setGooglePayData({
            gatewayMerchantId: this.options.googlePay.gatewayMerchantId,
            merchantInfo: {
                merchantName: this.options.googlePay.merchantName,
                merchantId: this.options.googlePay.merchantId,
            },
            transactionInfo: {
                currencyCode: this.options.googlePay.currency,
                countryCode: this.options.googlePay.countryCode,
                totalPriceStatus: 'ESTIMATED',
                checkoutOption: 'DEFAULT',
                totalPrice: String(this.options.googlePay.amount),
            },
            buttonOptions: {
                buttonColor: this.options.googlePay.buttonColor,
                buttonSizeMode: this.options.googlePay.buttonSizeMode,
            },
            allowedCardNetworks: this.options.googlePay.allowedCardNetworks,
            allowCreditCards: this.options.googlePay.allowCreditCards,
            allowPrepaidCards: this.options.googlePay.allowPrepaidCards,
            billingAddressParameters: { format: 'MIN' },
            billingAddressRequired: true,
            emailRequired: true,
            onPaymentDataChangedCallback: () => {
                return {};
            },
            shippingOptionParameters: {},
            onPaymentAuthorizedCallback: async (
                paymentData,
                approve,
                reject
            ) => {
                console.log('paymentData:', paymentData);
                // You can create customer here based on paymentData
                const response = await unzerExpressPayment.submit();
                const paymentTypeId = response.submitResponse.data.id;
                console.log(
                    response,
                    '--- success paymentTypeId',
                    paymentTypeId
                );
                console.log('submit response: ', response);

                fetch('/unzer/google-pay-express', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        paymentTypeId,
                        paymentData,
                    }),
                })
                    .then((response) => response.json())
                    .then((json) => {
                        console.log(JSON.stringify(json));
                        location.href = json.redirectUrl;
                    });
            },
            shippingAddressRequired: true,
            shippingOptionRequired: false,
        });
    }

    registerApplePay(applePayButton, unzerExpressPayment) {
        const applePayPaymentRequest = {
            countryCode: this.options.applePay.countryCode,
            currencyCode: this.options.applePay.currency,
            supportedNetworks: this.options.applePay.supportedNetworks,
            merchantCapabilities: this.options.applePay.merchantCapabilities,
            total: {
                label: this.options.applePay.shopName,
                amount: String(this.options.applePay.amount),
            },
            requiredShippingContactFields: [
                'postalAddress',
                'name',
                'email',
                'phone',
            ],
            requiredBillingContactFields: [
                'postalAddress',
                'name',
                'email',
                'phone',
            ],

            onPaymentAuthorizedCallback: async (
                paymentData,
                approve,
                reject
            ) => {
                console.log('paymentData:', paymentData);
                let shippingContact = event.payment.shippingContact; // Store the shipping contact data for express checkout
                let billingContact = event.payment.billingContact; // Store the billing contact data for express checkout

                console.log(shippingContact);
                console.log(billingContact);

                // You can create customer here based on paymentData
                const response = await unzerExpressPayment.submit();
                const paymentTypeId = response.submitResponse.data.id;
                console.log(
                    response,
                    '--- success paymentTypeId',
                    paymentTypeId
                );
                console.log('submit response: ', response);

                fetch('/unzer/applepay-express', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        paymentTypeId,
                        paymentData,
                        shippingContact,
                        billingContact,
                    }),
                })
                    .then((response) => response.json())
                    .then((json) => {
                        console.log(JSON.stringify(json));
                        location.href = json.redirectUrl;
                    });
            },
        };
        applePayPaymentRequest.initApplePaySession = (applePaySession) => {
            const session = applePaySession;
        };
        unzerExpressPayment?.setApplePayData(applePayPaymentRequest);
    }
}
