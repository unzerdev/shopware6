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
    static unzerPlugin = null;

    init() {
        this.unzerPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.eps = this.unzerPlugin.unzerInstance.EPS();

        this._createForm();
        this._registerEvents();
    }

    /**
     * @private
     */
    _createForm() {
        this.eps.create('eps', {
            containerId: 'unzer-eps-container'
        });
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
