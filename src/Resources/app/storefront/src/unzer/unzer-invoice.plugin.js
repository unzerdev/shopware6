import Plugin from 'src/plugin-system/plugin.class';

export default class UnzerPaymentInvoicePlugin extends Plugin {
    static options = {
        unzerPaymentCardId: 'unzer-card'
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static invoice;

    /**
     * @type {UnzerPaymentBasePlugin}
     *
     * @private
     */
    static unzerPaymentPlugin = null;

    init() {
        this.unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.invoice = this.unzerPaymentPlugin.unzerInstance.Invoice();

        this._registerEvents();
    }

    /**
     * @private
     */
    _registerEvents() {
        this.unzerPaymentPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource(), {
            scope: this
        });
    }

    /**
     * @private
     */
    _onCreateResource() {
        this.unzerPaymentPlugin.setSubmitButtonActive(false);

        this.invoice.createResource()
            .then((resource) => this._submitPayment(resource))
            .catch((error) => this._handleError(error));
    }

    /**
     * @param {Object} resource
     *
     * @private
     */
    _submitPayment(resource) {
        this.unzerPaymentPlugin.submitResource(resource);
    }

    /**
     * @param {Object} error
     *
     * @private
     */
    _handleError(error) {
        this.unzerPaymentPlugin.showError(error);
    }
}
