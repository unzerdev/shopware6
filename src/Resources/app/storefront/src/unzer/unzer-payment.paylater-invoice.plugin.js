import Plugin from 'src/plugin-system/plugin.class';

export default class UnzerPaymentPaylaterInvoicePlugin extends Plugin {
    static options = {
        isB2BCustomer: false,
        customerInfo: null,
        customerId: ''
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

    /**
     * @private
     */
    static _b2bCustomer = null;

    init() {
        this._unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.paylaterInvoice = this._unzerPaymentPlugin.unzerInstance.PaylaterInvoice();

        if (this.options.isB2BCustomer) {
            this._createB2bForm();
        }

        this.paylaterInvoice.create({
            containerId: 'unzer-payment-paylater-invoice-wrapper',
            customerType: this.options.isB2BCustomer ? 'B2B' : 'B2C'
        })

        this._registerEvents();
    }

    /**
     * @private
     */
    _createB2bForm() {
        const me = this;

        this._b2bCustomer = this._unzerPaymentPlugin.unzerInstance.B2BCustomer();

        this._b2bCustomer.addEventListener('validate', function (event) {
            me._unzerPaymentPlugin.setSubmitButtonActive(event.success);
        });

        if (this.options.customerId) {
            this._b2bCustomer.initFormFields(this._unzerPaymentPlugin.getB2bCustomerObject(this.options.customerInfo));
            this._b2bCustomer.update(this.options.customerId, {
                containerId: 'unzer-payment-b2b-form',
            });

            return;
        }

        this._b2bCustomer.create({
            containerId: 'unzer-payment-b2b-form',
            paymentTypeName: 'paylater-invoice'
        });
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

        if (this.options.isB2BCustomer) {
            if (this.options.customerId) {
                this._b2bCustomer.updateCustomer()
                    .then((data) => this._onB2bCustomerUpdated(data.id))
                    .catch((error) => this._handleError(error));

                return;
            }

            this._b2bCustomer.createCustomer()
                .then((data) => this._onB2bCustomerUpdated(data.id))
                .catch((error) => this._handleError(error));

            return;
        }

        this._createResource();
    }

    /**
     * @private
     */
    _createResource() {
        this.paylaterInvoice.createResource()
            .then((resource) => this._submitPayment(resource))
            .catch((error) => this._handleError(error));
    }

    /**
     * @param {String} customerId
     * @private
     */
    _onB2bCustomerUpdated(customerId) {
        const resourceIdElement = document.getElementById('unzerCustomerId');
        resourceIdElement.value = customerId;

        this._createResource();
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
