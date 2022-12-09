import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class UnzerPaymentApplePayPlugin extends Plugin {
    static options = {
        countryCode: 'DE',
        currency: 'EUR',
        shopName: 'Unzer GmbH',
        amount: '0.0',
        lineItems: [],
        applePayButtonSelector: 'apple-pay-button',
        checkoutConfirmButtonSelector: '#confirmFormSubmit'
    };

    /**
     * @type {Boolean}
     */
    static submitting = false;

    /**
     * @type {UnzerPaymentBasePlugin}
     *
     * @private
     */
    static _unzerPaymentPlugin = null;

    /**
     * @type {Object}
     *
     * @public
     */
    static applePay;

    init() {
        this._unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];

        this._createScript();
        this._createForm();
        this._registerEvents();
    }

    _createScript() {
        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = 'https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js';

        document.head.appendChild(script);
    }

    /**
     * @private
     */
    _createForm() {
        this.applePay = this._unzerPaymentPlugin.unzerInstance.ApplePay();

        const confirmButton = DomAccess.querySelector(document, this.options.checkoutConfirmButtonSelector);

        // TODO: Make sure we have Apple Pay capabilities, show error otherwise
        confirmButton.style.display = 'none';
    }

    _startPayment() {
        const applePayPaymentRequest = {
            countryCode: this.options.countryCode,
            currencyCode: this.options.currency,
            supportedNetworks: ['visa', 'masterCard'],
            merchantCapabilities: ['supports3DS'],
            total: { label: this.options.shopName, amount: this.options.amount },
            lineItems: [ // TODO
                {
                    "label": "Subtotal",
                    "type": "final",
                    "amount": "10.00"
                },
                {
                    "label": "Free Shipping",
                    "amount": "0.00",
                    "type": "final"
                },
                {
                    "label": "Estimated Tax",
                    "amount": "2.99",
                    "type": "final"
                }
            ]
        };

        // We adhere to Apple Pay version 6 to handle the payment request.
        const session = new ApplePaySession(6, applePayPaymentRequest);
        session.onvalidatemerchant = function (event) {
            // Call the merchant validation in your server-side integration
            const merchantSession = ''; // TODO: Response from merchant validation

            session.completeMerchantValidation(merchantSession);
        }

        const me = this;
        session.onpaymentauthorized = function (event) {
            // The event will contain the data you need to pass to our server-side integration to actually charge the customers card
            const paymentData = event.payment.token.paymentData;
            // event.payment also contains contact information if needed.

            // Create the payment method instance at Unzer with your public key
            me.applePay.createResource(paymentData)
                .then(function (createdResource) {
                    // Hand over the payment type ID (createdResource.id) to your backend.
                    me.submitting = true;
                    me._unzerPaymentPlugin.setSubmitButtonActive(false);
                    me._unzerPaymentPlugin.submitResource(createdResource); // TODO: Is this right?
                })
                .catch(function (error) {
                    // Handle the error. E.g. show error.message in the frontend.
                    abortPaymentSession(session);
                })
                .finally(function () {
                    me._unzerPaymentPlugin.setSubmitButtonActive(true);
                    me.submitting = false;
                });
        }

        // Add additional event handler functions ...

        session.begin();
    }

    /**
     * @private
     */
    _registerEvents() {
        const applePayButton = DomAccess.querySelector(document, this.options.applePayButtonSelector);

        applePayButton.addEventListener('click', this._startPayment.bind(this));
    }

    /**
     * @param {Object} error
     *
     * @private
     */
    _handleError(error) {
        this._unzerPaymentPlugin.showError(error);
    }
}
