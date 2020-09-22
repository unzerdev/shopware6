import Plugin from 'src/plugin-system/plugin.class';

export default class UnzerIdealPlugin extends Plugin {
    /**
     * @type {Object}
     *
     * @public
     */
    static ideal;

    /**
     * @type {UnzerPaymentBasePlugin}
     *
     * @private
     */
    static heidelpayPlugin = null;

    init() {
        this.unzerPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.ideal = this.unzerPlugin.unzerInstance.Ideal();

        this._createForm();
        this._registerEvents();
    }

    /**
     * @private
     */
    _createForm() {
        this.ideal.create('ideal', {
            containerId: 'unzer-ideal-container'
        });

        this.unzerPlugin.setSubmitButtonActive(false);
    }

    /**
     * @private
     */
    _registerEvents() {
        this.unzerPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource(), {
            scope: this
        });

        if (this.ideal) {
            this.ideal.addEventListener('change', (event) => this._onFormChange(event), {
                scope: this
            });
        }
    }

    /**
     * @private
     */
    _onFormChange(event) {
        if (event.value) {
            this.unzerPlugin.setSubmitButtonActive(true);
        }
    }

    /**
     * @private
     */
    _onCreateResource() {
        this.unzerPlugin.setSubmitButtonActive(false);

        this.ideal.createResource()
            .then((resource) => this.unzerPlugin.submitResource(resource))
            .catch((error) => this.unzerPlugin.showError(error));
    }
}
