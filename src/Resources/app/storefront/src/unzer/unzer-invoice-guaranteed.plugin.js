import Plugin from 'src/plugin-system/plugin.class';

export default class UnzerInvoiceGuaranteedPlugin extends Plugin {
    static options = {
        isB2BCustomer: false,
        customerInfo: null
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static invoiceGuaranteed;

    /**
     * @type {UnzerPaymentBasePlugin}
     *
     * @private
     */
    static unzerPaymentPlugin = null;

    /**
     * @type {Object}
     */
    static b2bCustomerProvider = null;

    init() {
        this.unzerPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.invoiceGuaranteed = this.unzerPlugin.unzerInstance.InvoiceGuaranteed();

        if (this.options.isB2BCustomer) {
            this._createB2bForm();
        }

        this._registerEvents();
    }

    /**
     * @private
     */
    _registerEvents() {
        this.unzerPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource(), {
            scope: this
        });
    }

    /**
     * @private
     */
    _createB2bForm() {
        this.b2bCustomerProvider = this.unzerPlugin.unzerInstance.B2BCustomer();

        this.b2bCustomerProvider.b2bCustomerEventHandler = (event) => this._onValidateB2bForm(event);
        this.b2bCustomerProvider.initFormFields(this.unzerPlugin.getB2bCustomerObject(this.options.customerInfo));

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
        this.unzerPlugin.setSubmitButtonActive(event.success);
    }

    /**
     * @private
     */
    _onCreateResource() {
        this.unzerPlugin.setSubmitButtonActive(false);

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
        this.unzerPlugin.submitResource(resource);
    }

    /**
     * @param {Object} error
     *
     * @private
     */
    _handleError(error) {
        this.unzerPlugin.showError(error);
    }
}
