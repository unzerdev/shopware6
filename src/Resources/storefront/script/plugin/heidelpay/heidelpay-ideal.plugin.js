import Plugin from 'src/script/plugin-system/plugin.class';

export default class HeidelpayIdealPlugin extends Plugin {
    /**
     * @type {Object}
     *
     * @public
     */
    static ideal;

    /**
     * @type {HeidelpayBasePlugin}
     *
     * @private
     */
    static heidelpayPlugin = null;

    init() {
        this.heidelpayPlugin = window.PluginManager.getPluginInstances('HeidelpayBase')[0];
        this.ideal = this.heidelpayPlugin.heidelpayInstance.Ideal();

        this._createForm();
        this._registerEvents();
    }

    _createForm() {
        this.ideal.create('ideal', {
            containerId: 'heidelpay-ideal-container',
        });
    }

    _registerEvents() {
        this.heidelpayPlugin.$emitter.subscribe('heidelpayBase_createResource', () => this._onCreateResource(), {
            scope: this,
        });
    }

    _onCreateResource() {
        this.heidelpayPlugin.setSubmitButtonActive(false);

        this.ideal.createResource()
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
