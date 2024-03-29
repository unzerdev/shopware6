import Plugin from 'src/plugin-system/plugin.class';

export default class UnzerPaymentInvoiceSecuredPlugin extends Plugin {
    static options = {
        isB2BCustomer: false,
        customerInfo: null
    };

    /**
     * @type {null|UnzerPaymentBasePlugin}
     *
     * @private
     */
    static _unzerPaymentPlugin = null;

    /**
     * @type {null|InvoiceSecured}
     *
     * @public
     */
    static invoiceSecured = null;

    /**
     * @type {null|b2bCustomer}
     */
    static b2bCustomerProvider = null;

    init() {
        this._unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.invoiceSecured = this._unzerPaymentPlugin.unzerInstance.InvoiceSecured();

        if (this.options.isB2BCustomer) {
            this._createB2bForm();
        }

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
    _createB2bForm() {
        this.b2bCustomerProvider = this._unzerPaymentPlugin.unzerInstance.B2BCustomer();

        this.b2bCustomerProvider.b2bCustomerEventHandler = (event) => this._onValidateB2bForm(event);
        this.b2bCustomerProvider.initFormFields(this._unzerPaymentPlugin.getB2bCustomerObject(this.options.customerInfo));

        this.b2bCustomerProvider.create({
            containerId: 'unzer-payment-b2b-form',
            externalCustomerId: this.options.customerInfo.customerNumber
        });
    }

    /**
     * @param {Object} event
     *
     * @private
     */
    _onValidateB2bForm(event) {
        this._unzerPaymentPlugin.setSubmitButtonActive(event.success);
    }

    /**
     * @private
     */
    _onCreateResource() {
        this._unzerPaymentPlugin.setSubmitButtonActive(false);

        if (this.options.isB2BCustomer) {
            this.b2bCustomerProvider.createCustomer()
                .then((data) => this._onB2bCustomerCreated(data.id))
                .catch((error) => this._handleError(error));
        } else {
            this.invoiceSecured.createResource()
                .then((resource) => this._submitPayment(resource))
                .catch((error) => this._handleError(error));
        }
    }

    /**
     * @param {String} b2bCustomerId
     *
     * @private
     */
    _onB2bCustomerCreated(b2bCustomerId) {
        const resourceIdElement = document.getElementById('unzerCustomerId');
        resourceIdElement.value = b2bCustomerId;

        this.invoiceSecured.createResource()
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
