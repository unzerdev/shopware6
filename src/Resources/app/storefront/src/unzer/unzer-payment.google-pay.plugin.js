import UnzerPaymentBaseParent from './parents/unzer-payment.base-parent';

export default class UnzerPaymentGooglePayPlugin extends UnzerPaymentBaseParent {
    static options = {
        googlePayButtonId: 'unzer-google-pay-button',
        merchantName: '',
        merchantId: '',
        gatewayMerchantId: '',
        currency: 'EUR',
        amount: '0.0',
        countryCode: 'DE',
        allowedCardNetworks: [],
        allowCreditCards: true,
        allowPrepaidCards: true,
        buttonColor: 'default',
        buttonSizeMode: 'fill',
    };

    /**
     * @type {Boolean}
     */
    static submitting = false;

    init() {
        super.init();

        this._registerGooglePayButton();
        this._hideBuyButton();
    }

    /**
     * @private
     */
    _registerGooglePayButton() {
        Promise.all([
            customElements.whenDefined('unzer-payment'),
            customElements.whenDefined('unzer-checkout'),
        ]).then(() => {
            const unzerPaymentElement = document.getElementById(
                'unzer-payment-component'
            );
            if (unzerPaymentElement) {
                unzerPaymentElement.setGooglePayData({
                    gatewayMerchantId: this.options.gatewayMerchantId,
                    merchantInfo: {
                        merchantName: this.options.merchantName,
                        merchantId: this.options.merchantId,
                    },
                    transactionInfo: {
                        currencyCode: this.options.currency,
                        countryCode: this.options.countryCode,
                        totalPriceStatus: 'ESTIMATED',
                        totalPrice: String(this.options.amount),
                    },
                    buttonOptions: {
                        buttonColor: this.options.buttonColor,
                        buttonSizeMode: this.options.buttonSizeMode,
                    },
                    // onPaymentAuthorizedCallback: async (paymentData, approve, reject) => {
                    //     if (!this._unzerPaymentPlugin._validateForm()) {
                    //         reject({message: 'Payment requirements not met'});
                    //     } else {
                    //         const response = await unzerPaymentElement.submit();
                    //         if (response.submitResponse.success) {
                    //             approve();
                    //         } else {
                    //             reject({message: 'Payment processing failed'});
                    //         }
                    //     }
                    // },
                    allowedCardNetworks: this.options.allowedCardNetworks,
                    allowCreditCards: this.options.allowCreditCards,
                    allowPrepaidCards: this.options.allowPrepaidCards,
                });
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
                    } else {
                        console.error(response);
                    }
                };
            }
        });
    }

    /**
     * @private
     */
    _hideBuyButton() {
        const confirmButton = this._getSubmitButton();
        confirmButton.style.display = 'none';
    }
}
