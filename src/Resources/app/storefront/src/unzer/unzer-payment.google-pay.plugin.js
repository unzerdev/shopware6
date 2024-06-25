import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class UnzerPaymentApplePayPlugin extends Plugin {
    static options = {
        googlePayButtonId: 'unzer-google-pay-button',
        checkoutConfirmButtonSelector: '#confirmFormSubmit',

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

    /**
     * @type {UnzerPaymentBasePlugin}
     *
     * @private
     */
    static _unzerPaymentPlugin = null;

    /**
     * @type {HttpClient}
     *
     * @public
     */
    static client;

    init() {
        this._unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.client = new HttpClient();
        this.googlePayInstance = this._unzerPaymentPlugin.unzerInstance.Googlepay();

        this._createScript(() => {
            this._registerGooglePayButton();
        });
        this._hideBuyButton();
    }

    /**
     * @private
     */
    _registerGooglePayButton() {
        const me = this;

        const paymentDataRequestObject = this.googlePayInstance.initPaymentDataRequestObject(
            {
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
                allowedCardNetworks: this.options.allowedCardNetworks,
                allowCreditCards: this.options.allowCreditCards,
                allowPrepaidCards: this.options.allowPrepaidCards,

                onPaymentAuthorizedCallback: (paymentData) => {
                    const googlePayButton = document.getElementById(me.options.googlePayButtonId);
                    googlePayButton.style.display = 'none';
                    return me.googlePayInstance.createResource(paymentData)
                        .then(
                            (createdResource) => {
                                if (me._unzerPaymentPlugin._validateForm() !== false) {
                                    me._unzerPaymentPlugin.submitting = true;
                                    me._unzerPaymentPlugin.submitResource(createdResource);
                                }else{
                                    googlePayButton.style.display = '';
                                }
                                return {
                                    status: 'success'
                                };
                            }
                        )
                        .catch(
                            (error) => {
                                googlePayButton.style.display = '';
                                const publicError = error;
                                publicError.message = error.customerMessage || error.message || 'Error';
                                me._handleError(publicError);
                                return {
                                    status: 'error',
                                    message: publicError.message || 'Unexpected error'
                                }
                            }
                        )
                }
            }
        );
        this.googlePayInstance.create(
            {
                containerId: me.options.googlePayButtonId,
            },
            paymentDataRequestObject
        );
    }

    /**
     * @private
     */
    _createScript(onloadCallback) {
        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = 'https://pay.google.com/gp/p/js/pay.js';
        script.onload = onloadCallback;

        document.head.appendChild(script);
    }

    /**
     * @private
     */
    _hideBuyButton() {
        const confirmButton = DomAccess.querySelector(document, this.options.checkoutConfirmButtonSelector);
        confirmButton.style.display = 'none';
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
