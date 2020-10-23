import Plugin from 'src/plugin-system/plugin.class';

export default class UnzerPaymentEpsPlugin extends Plugin {
    /**
     * @type {Object}
     *
     * @public
     */
    static eps;

    /**
     * @type {UnzerPaymentBasePlugin}
     *
     * @private
     */
    static _unzerPaymentPlugin = null;

    init() {
        this._unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.eps = this._unzerPaymentPlugin.unzerInstance.EPS();

        this._createForm();
        this._registerEvents();
    }

    /**
     * @private
     */
    _createForm() {
        this.eps.create('eps', {
            containerId: 'unzer-payment-eps-container'
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

        this.eps.createResource()
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
