import Plugin from 'src/plugin-system/plugin.class';

export default class HeidelpayInvoicePlugin extends Plugin {
    static options = {
        heidelpayCardId: 'heidelpay-card',
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static invoice;

    /**
     * @type {HeidelpayBasePlugin}
     *
     * @private
     */
    static heidelpayPlugin = null;

    init() {
        /*
            Hiding the heidelpay card is special for invoice payments.
            The heidelpay JS SDK needs to create an own resource (payment-type) but does not need any further input,
            therefore we can simply hide the heidelpay card on the confirm page.
         */
        this._hideHeidelpayCard();

        this.heidelpayPlugin = window.PluginManager.getPluginInstances('HeidelpayBase')[0];
        this.invoice = this.heidelpayPlugin.heidelpayInstance.Invoice();

        this._registerEvents();
    }

    /**
     * @private
     */
    _registerEvents() {
        this.heidelpayPlugin.$emitter.subscribe('heidelpayBase_createResource', () => this._onCreateResource(), {
            scope: this,
        });
    }

    /**
     * @private
     */
    _onCreateResource() {
        this.heidelpayPlugin.setSubmitButtonActive(false);

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

    /**
     * @private
     */
    _hideHeidelpayCard() {
        const heidelpayCard = document.getElementById(this.options.heidelpayCardId);

        heidelpayCard.hidden = true;
    }
}
