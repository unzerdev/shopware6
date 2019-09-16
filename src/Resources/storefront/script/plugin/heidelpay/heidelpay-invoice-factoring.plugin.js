import Plugin from 'src/script/plugin-system/plugin.class';

export default class HeidelpayInvoiceFactoringPlugin extends Plugin {
    /**
     * @type {Object}
     *
     * @public
     */
    static invoiceFactoring;

    /**
     * @type {HeidelpayBasePlugin}
     *
     * @private
     */
    static heidelpayPlugin = null;

    init() {
        this.heidelpayPlugin = window.PluginManager.getPluginInstances('HeidelpayBase')[0];
        this.invoiceFactoring = this.heidelpayPlugin.heidelpayInstance.InvoiceFactoring();

        this._registerEvents();
    }

    _registerEvents() {
        this.heidelpayPlugin.$emitter.subscribe('heidelpayBase_createResource', () => this._onCreateResource(), {
            scope: this,
        });
    }

    _onCreateResource() {
        this.heidelpayPlugin.setSubmitButtonActive(false);

        this.invoiceFactoring.createResource()
            .then((resource) => this._submitPayment(resource))
            .catch((error) => this._handleError(error));
    }


    /**
     * @param {Object} resource
     * @private
     */
    _submitPayment(resource) {
        this.heidelpayPlugin.submitResource(resource);
    }

    /**
     * @param {Object} error
     *
     * @private
     */
    _handleError(error) {
        this.heidelpayPlugin.showError(error);
    }
}
