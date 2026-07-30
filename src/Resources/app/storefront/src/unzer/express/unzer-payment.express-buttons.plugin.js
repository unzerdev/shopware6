const Plugin = window.PluginBaseClass;

export default class UnzerPaymentExpressButtonsPlugin extends Plugin {
    static options = {
        googlePay: {},
        applePay: {},
        keyPairConfig: null,
        urls: {},
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

    showError() {
        const errorElement = this.el.querySelector(
            '.unzer-express-buttons-error'
        );
        if (errorElement) {
            errorElement.removeAttribute('hidden');
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
            if (this.options.keyPairConfig) {
                unzerExpressPayment.setMerchantConfigData(
                    this.options.keyPairConfig
                );
            }
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
                const container = unzerGooglePay.parentNode;
                unzerGooglePay.remove();
                container.appendChild(unzerGooglePay);
            }

            if (unzerApplePay) {
                this.registerApplePay(unzerApplePay, unzerExpressPayment);
                const container = unzerApplePay.parentNode;
                unzerApplePay.remove();
                container.appendChild(unzerApplePay);
            }
        });
    }

    registerPaypalExpress(paypalButton, unzerExpressPayment) {
        paypalButton.id =
            'unzer-paypal-button-' + Math.floor(Math.random() * 10000);
        paypalButton.addEventListener('click', async (event) => {
            event.stopPropagation();
            const response = await unzerExpressPayment.submit();

            if (!response?.submitResponse?.success) {
                this.showError();
                return;
            }
            const paymentTypeId = response.submitResponse.data.id;
            fetch(this.options.urls.paypal, {
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
                })
                .catch(() => {
                    this.showError();
                });
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
                // You can create customer here based on paymentData
                const response = await unzerExpressPayment.submit();
                if (!response?.submitResponse?.success) {
                    this.showError();
                    return;
                }
                const paymentTypeId = response.submitResponse.data.id;

                fetch(this.options.urls.googlePay, {
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
                        location.href = json.redirectUrl;
                    })
                    .catch(() => {
                        this.showError();
                    });
            },
            shippingAddressRequired: true,
            shippingOptionRequired: false,
        });
    }

    registerApplePay(applePayButton, unzerExpressPayment) {
        console.log('register amount', this.options.applePay.amount);
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
                reject,
                event
            ) => {
                let shippingContact = event.payment.shippingContact; // Store the shipping contact data for express checkout
                let billingContact = event.payment.billingContact; // Store the billing contact data for express checkout

                // You can create customer here based on paymentData
                const response = await unzerExpressPayment.submit();
                if (response.submitResponse.success) {
                    approve();
                } else {
                    reject();
                    this.showError();
                    return;
                }
                const paymentTypeId = response.submitResponse.data.id;

                fetch(this.options.urls.applePay, {
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
                        location.href = json.redirectUrl;
                    })
                    .catch(() => {
                        this.showError();
                    });
            },
        };
        applePayPaymentRequest.initApplePaySession = (applePaySession) => {
            const session = applePaySession;
        };
        unzerExpressPayment?.setApplePayData(applePayPaymentRequest);
    }
}
