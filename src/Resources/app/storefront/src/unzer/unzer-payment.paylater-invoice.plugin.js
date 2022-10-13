import Plugin from 'src/plugin-system/plugin.class';

export default class UnzerPaymentPaylaterInvoicePlugin extends Plugin {
    static options = {
        isB2BCustomer: false
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static paylaterInvoice;

    /**
     * @type {UnzerPaymentBasePlugin}
     *
     * @private
     */
    static _unzerPaymentPlugin = null;

    init() {
        this._unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.paylaterInvoice = this._unzerPaymentPlugin.unzerInstance.PaylaterInvoice();

        this.paylaterInvoice.create({
            containerId: 'unzer-payment-paylater-invoice-wrapper',
            customerType: this.options.isB2BCustomer ? 'B2B' : 'B2C'
        })

        this._registerEvents();
    }

    /**
     * @private
     */
    _registerEvents() {
        this._unzerPaymentPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource(), {
            scope: this
        });
    }

    /**
     * @private
     */
    _onCreateResource() {
        this._unzerPaymentPlugin.setSubmitButtonActive(false);

        this.paylaterInvoice.createResource()
            .then((resource) => this._submitPayment(resource))
            .catch((error) => this._handleError(error));
    }

    /**
     * @param {Object} resource
     *
     * @private
     */
    _submitPayment(resource) {
        this._unzerPaymentPlugin.submitResource(resource);
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
