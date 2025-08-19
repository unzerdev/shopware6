const Plugin = window.PluginBaseClass;


import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';

export default class UnzerPaymentApplePayPlugin extends Plugin {
    static options = {
        countryCode: 'DE',
        currency: 'EUR',
        shopName: 'Unzer GmbH',
        amount: '0.0',
        applePayButtonSelector: '.apple-pay-button',
        checkoutConfirmButtonSelector: '#confirmFormSubmit',
        applePayMethodSelector: '.unzer-payment-apple-pay-v2-method-wrapper',
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
        this._unzerPaymentPlugin =
            window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        

        if (this._hasCapability()) {
            this._createForm();
            this._registerEvents();
        } else {
            this._disableApplePay();
        }
    }

    _hasCapability() {
        return (
            window.ApplePaySession &&
            window.ApplePaySession.canMakePayments() &&
            window.ApplePaySession.supportsVersion(6)
        );
    }

    _disableApplePay() {
        document.querySelector(this.options.applePayMethodSelector).remove();
        document.querySelectorAll('[data-unzer-payment-apple-pay-v2]').forEach((pluginElement) => pluginElement.remove());
        this._unzerPaymentPlugin.showError({
            message: this.options.noApplePayMessage,
        });
        this._unzerPaymentPlugin.setSubmitButtonActive(false);
    }

    /**
     * @private
     */
    _createForm() {
        this.applePay = this._unzerPaymentPlugin.unzerInstance.ApplePay();

        const confirmButton = document.querySelector(this.options.checkoutConfirmButtonSelector);
        confirmButton.style.display = 'none';
    }

    _startPayment() {
        if (!this._unzerPaymentPlugin._validateForm()) {
            return;
        }
        const me = this;
        const applePayPaymentRequest = {
            countryCode: this.options.countryCode,
            currencyCode: this.options.currency,
            supportedNetworks: this.options.supportedNetworks,
            merchantCapabilities: this.options.merchantCapabilities,
            total: {
                label: this.options.shopName,
                amount: this.options.amount,
            },
        };

        if (!window.ApplePaySession) {
            return;
        }

        const session = this.applePay.initApplePaySession(
            applePayPaymentRequest
        );

        session.onpaymentauthorized = (event) => {
            const paymentData = event.payment.token.paymentData;

            me.applePay
                .createResource(paymentData)
                .then((createdResource) => {
                    me._unzerPaymentPlugin.setSubmitButtonActive(false);
                    me._unzerPaymentPlugin.submitting = true;
                    me._unzerPaymentPlugin.submitResource(createdResource);
                })
                .catch(() => {
                    PageLoadingIndicatorUtil.remove();
                    session.abort();
                })
                .finally(() => {
                    me._unzerPaymentPlugin.setSubmitButtonActive(true);
                    me._unzerPaymentPlugin.submitting = false;
                });
        };
        session.begin();
    }

    /**
     * @private
     */
    _registerEvents() {
        const applePayButton = document.querySelector(this.options.applePayButtonSelector);

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
