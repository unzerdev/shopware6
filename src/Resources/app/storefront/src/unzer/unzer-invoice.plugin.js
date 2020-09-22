import Plugin from 'src/plugin-system/plugin.class';

export default class UnzerInvoicePlugin extends Plugin {
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
        /*
            Hiding the unzerPayment card is special for invoice payments.
            The unzerPayment JS SDK needs to create an own resource (payment-type) but does not need any further input,
            therefore we can simply hide the unzerPayment card on the confirm page.
         */
        this._hideUnzerPaymentCard();

        this.unzerPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.invoice = this.unzerPlugin.unzerInstance.Invoice();

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
    _onCreateResource() {
        this.unzerPlugin.setSubmitButtonActive(false);

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

    /**
     * @private
     */
    _hideUnzerPaymentCard() {
        const unzerPaymentCard = document.getElementById(this.options.unzerCardId);

        unzerPaymentCard.hidden = true;
    }
}
