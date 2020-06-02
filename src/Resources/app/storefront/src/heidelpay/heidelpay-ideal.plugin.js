import Plugin from 'src/plugin-system/plugin.class';

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

    /**
     * @private
     */
    _createForm() {
        this.ideal.create('ideal', {
            containerId: 'heidelpay-ideal-container'
        });
    }

    /**
     * @private
     */
    _registerEvents() {
        this.heidelpayPlugin.$emitter.subscribe('heidelpayBase_createResource', () => this._onCreateResource(), {
            scope: this
        });
    }

    /**
     * @private
     */
    _onCreateResource() {
        this.heidelpayPlugin.setSubmitButtonActive(false);

        this.ideal.createResource()
            .then((resource) => this.heidelpayPlugin.submitResource(resource))
            .catch((error) => this.heidelpayPlugin.showError(error));
    }
}
