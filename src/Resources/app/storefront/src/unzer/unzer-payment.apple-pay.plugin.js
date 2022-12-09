import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from "src/service/http-client.service";

export default class UnzerPaymentApplePayPlugin extends Plugin {
    static options = {
        countryCode: 'DE',
        currency: 'EUR',
        shopName: 'Unzer GmbH',
        amount: '0.0',
        applePayButtonSelector: 'apple-pay-button',
        checkoutConfirmButtonSelector: '#confirmFormSubmit',
        applePayMethodSelector: '.unzer-payment-apple-pay-method-wrapper',
        authorizePaymentUrl: '',
        merchantValidationUrl: '',
        noApplePayMessage: '',
        supportedNetworks: ['masterCard', 'visa'],
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
     * @type {ApplePay}
     *
     * @public
     */
    static applePay;

    /**
     * @type {HttpClient}
     *
     * @public
     */
    static client;

    init() {
        this._unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.client = new HttpClient();

        if (this._hasCapability()) {
            this._createScript();
            this._createForm();
            this._registerEvents();
        } else {
            this._disableApplePay();
        }
    }

    _hasCapability() {
        return window.ApplePaySession && window.ApplePaySession.canMakePayments() && window.ApplePaySession.supportsVersion(6)
    }

    _disableApplePay() {
        DomAccess.querySelector(document, this.options.applePayMethodSelector, false).remove();
        DomAccess.querySelectorAll(document, '[data-unzer-payment-apple-pay]', false).forEach((pluginElement) => pluginElement.remove());
        this._unzerPaymentPlugin.showError({ message: this.options.noApplePayMessage });
        this._unzerPaymentPlugin.setSubmitButtonActive(false);
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
        confirmButton.style.display = 'none';
    }

    _startPayment() {
        const me = this;
        const applePayPaymentRequest = {
            countryCode: this.options.countryCode,
            currencyCode: this.options.currency,
            supportedNetworks: this.options.supportedNetworks,
            merchantCapabilities: ['supports3DS'],
            total: { label: this.options.shopName, amount: this.options.amount },
        };

        const session = new ApplePaySession(6, applePayPaymentRequest);
        session.onvalidatemerchant = function (event) {
            try {
                me.client.post(me.options.merchantValidationUrl, { merchantValidationUrl: event.validationURL }, (response) => {
                    session.completeMerchantValidation(response);
                });
            } catch(e) {
                session.abort();
            }
        }

        session.onpaymentauthorized = function (event) {
            // The event will contain the data you need to pass to our server-side integration to actually charge the customers card
            const paymentData = event.payment.token.paymentData;
            // event.payment also contains contact information if needed.

            // Create the payment method instance at Unzer with your public key
            me.applePay.createResource(paymentData)
                .then(function (createdResource) {
                    me.submitting = true;

                    try {
                        me.client.post(me.options.authorizePaymentUrl, createdResource, (response) => {
                            responseData = JSON.parse(response);
                            if (response.transactionStatus === 'pending') {
                                session.completePayment({status: window.ApplePaySession.STATUS_SUCCESS});

                                me._unzerPaymentPlugin.setSubmitButtonActive(false);
                                me._unzerPaymentPlugin.submitResource(createdResource); // TODO: Is this right?
                            } else {
                                session.completePayment({status: window.ApplePaySession.STATUS_FAILURE});
                                session.abort();
                            }
                        });
                    } catch(e) {
                        session.completePayment({status: window.ApplePaySession.STATUS_FAILURE});
                        session.abort();
                    }
                })
                .catch(function (error) {
                    session.completePayment({status: window.ApplePaySession.STATUS_FAILURE});
                    session.abort();
                })
                .finally(function () {
                    me._unzerPaymentPlugin.setSubmitButtonActive(true);
                    me.submitting = false;
                });
        }

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
