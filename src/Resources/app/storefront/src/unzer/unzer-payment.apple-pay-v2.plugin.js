import UnzerPaymentBaseParent from './parents/unzer-payment.base-parent';

export default class UnzerPaymentApplePayPlugin extends UnzerPaymentBaseParent {
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

    init() {
        super.init();
        this._createForm();
        this._hideBuyButton();
    }

    _disableApplePay() {
        document.querySelector(this.options.applePayMethodSelector).remove();
        document
            .querySelectorAll('[data-unzer-payment-apple-pay-v2]')
            .forEach((pluginElement) => pluginElement.remove());
        this._handleError({ message: this.options.noApplePayMessage });
        this._unzerPaymentPlugin.setSubmitButtonActive(false);
    }

    /**
     * @private
     */
    _createForm() {
        Promise.all([customElements.whenDefined('unzer-payment')]).then(() => {
            const unzerPaymentElement = document.getElementById(
                'unzer-payment-component'
            );
            if (unzerPaymentElement) {
                unzerPaymentElement.setApplePayData(
                    this._getApplePayPaymentRequest()
                );
                const unzerCheckout = document.getElementById(
                    'unzer-checkout-component'
                );
                unzerCheckout.onPaymentSubmit = (response) => {
                    if (
                        response.submitResponse &&
                        response.submitResponse.success
                    ) {
                        if (!this._unzerPaymentPlugin._validateForm()) {
                            return;
                        }
                        unzerPaymentElement.style.display = 'none';
                        this._unzerPaymentPlugin.submitting = true;
                        this._unzerPaymentPlugin.submitTypeId(
                            response.submitResponse.data.id
                        );
                    }
                };
            }
        });
    }

    _getApplePayPaymentRequest() {
        return {
            countryCode: this.options.countryCode,
            currencyCode: this.options.currency,
            supportedNetworks: this.options.supportedNetworks,
            merchantCapabilities: this.options.merchantCapabilities,
            total: {
                label: this.options.shopName,
                amount: this.options.amount,
            },
        };
    }

    /**
     * @private
     */
    _hideBuyButton() {
        const confirmButton = document.querySelector(
            this.options.checkoutConfirmButtonSelector
        );
        confirmButton.style.display = 'none';
    }
}
