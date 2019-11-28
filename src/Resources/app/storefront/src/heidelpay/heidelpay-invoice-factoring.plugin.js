import Plugin from 'src/plugin-system/plugin.class';

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

    /**
     * @type {Object}
     *
     * @public
     */
    static b2bCustomer = null;

    init() {
        this.heidelpayPlugin = window.PluginManager.getPluginInstances('HeidelpayBase')[0];
        this.invoiceFactoring = this.heidelpayPlugin.heidelpayInstance.InvoiceFactoring();
        this.b2bCustomer = this.heidelpayPlugin.heidelpayInstance.B2BCustomer();

        this._createForm();
        this._registerEvents();
    }

    _createForm() {
        this.b2bCustomer.initFormFields({
            'companyInfo': {
                'commercialSector': 'AIR_TRANSPORT',
            },
        });

        this.b2bCustomer.create({
            containerId: 'heidelpay-invoice-commercial-sector',
        });
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
