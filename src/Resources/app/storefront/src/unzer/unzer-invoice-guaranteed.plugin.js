import Plugin from 'src/plugin-system/plugin.class';

export default class UnzerPaymentInvoiceGuaranteedPlugin extends Plugin {
    static options = {
        isB2BCustomer: false,
        customerInfo: null
    };

    /**
     * @type {null|UnzerPaymentBasePlugin}
     *
     * @private
     */
    static unzerPaymentPlugin = null;

    /**
     * @type {null|InvoiceGuaranteed}
     *
     * @public
     */
    static invoiceGuaranteed = null;

    /**
     * @type {null|b2bCustomer}
     */
    static b2bCustomerProvider = null;

    init() {
        this.unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.invoiceGuaranteed = this.unzerPaymentPlugin.unzerInstance.InvoiceGuaranteed();

        if (this.options.isB2BCustomer) {
            this._createB2bForm();
        }

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
    _createB2bForm() {
        this.b2bCustomerProvider = this.unzerPaymentPlugin.unzerInstance.B2BCustomer();

        this.b2bCustomerProvider.b2bCustomerEventHandler = (event) => this._onValidateB2bForm(event);
        this.b2bCustomerProvider.initFormFields(this.unzerPaymentPlugin.getB2bCustomerObject(this.options.customerInfo));

        this.b2bCustomerProvider.create({
            containerId: 'unzer-b2b-form'
        });
    }

    /**
     * @param {Object} event
     *
     * @private
     */
    _onValidateB2bForm(event) {
        this.unzerPaymentPlugin.setSubmitButtonActive(event.success);
    }

    /**
     * @private
     */
    _onCreateResource() {
        this.unzerPaymentPlugin.setSubmitButtonActive(false);

        if (this.options.isB2BCustomer) {
            this.b2bCustomerProvider.createCustomer()
                .then((data) => this._onB2bCustomerCreated(data.id))
                .catch((error) => this._handleError(error));
        } else {
            this.invoiceGuaranteed.createResource()
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

        this.invoiceGuaranteed.createResource()
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
